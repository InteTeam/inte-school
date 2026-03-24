<?php

declare(strict_types=1);

namespace Tests\Feature\Messaging;

use App\Events\MessageSent;
use App\Jobs\PromoteToSmsJob;
use App\Jobs\SendPushNotificationJob;
use App\Models\Message;
use App\Models\RegisteredDevice;
use App\Models\School;
use App\Models\User;
use App\Services\MessagingService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

final class NotificationCascadeTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private User $parent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create([
            'notification_settings' => [
                'sms_fallback_enabled' => true,
                'sms_timeout_seconds' => 900,
            ],
        ]);

        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
        $this->school->users()->attach($this->admin->id, [
            'id' => Str::ulid(),
            'role' => 'admin',
            'accepted_at' => now(),
            'invited_at' => now(),
        ]);

        $this->parent = User::factory()->create([
            'email' => 'parent@example.com',
            'phone' => '+447700900000',
        ]);
        $this->school->users()->attach($this->parent->id, [
            'id' => Str::ulid(),
            'role' => 'parent',
            'accepted_at' => now(),
            'invited_at' => now(),
        ]);
    }

    public function test_message_sent_event_is_fired_after_send(): void
    {
        Event::fake([MessageSent::class]);

        $service = app(MessagingService::class);
        $service->send(
            $this->school,
            $this->admin,
            ['type' => 'announcement', 'body' => 'Hello'],
            [$this->parent->id],
        );

        Event::assertDispatched(MessageSent::class, function (MessageSent $event): bool {
            return in_array($this->parent->id, $event->recipientIds, true);
        });
    }

    public function test_offline_user_receives_vapid_push(): void
    {
        Queue::fake();
        // Parent is NOT marked online → push should fire

        $device = RegisteredDevice::forceCreate([
            'school_id' => $this->school->id,
            'user_id' => $this->parent->id,
            'device_name' => 'Phone',
            'device_fingerprint' => Str::random(32),
            'push_subscription' => [
                'endpoint' => 'https://fcm.googleapis.com/test',
                'keys' => ['p256dh' => 'test', 'auth' => 'test'],
            ],
            'last_seen_at' => now(),
        ]);

        /** @var Message $message */
        $message = Message::forceCreate([
            'school_id' => $this->school->id,
            'sender_id' => $this->admin->id,
            'transaction_id' => (string) Str::ulid(),
            'type' => 'announcement',
            'body' => 'Hello',
            'requires_read_receipt' => false,
            'sent_at' => now(),
        ]);

        $service = app(NotificationService::class);
        $service->notifyRecipient($message, $this->parent->id);

        Queue::assertPushed(SendPushNotificationJob::class, function (SendPushNotificationJob $job) use ($device): bool {
            // Access via reflection to check the device matches
            $ref = new \ReflectionClass($job);
            $prop = $ref->getProperty('device');
            $prop->setAccessible(true);
            return $prop->getValue($job)->id === $device->id;
        });
    }

    public function test_online_user_does_not_receive_vapid_push(): void
    {
        Queue::fake();

        RegisteredDevice::forceCreate([
            'school_id' => $this->school->id,
            'user_id' => $this->parent->id,
            'device_name' => 'Phone',
            'device_fingerprint' => Str::random(32),
            'push_subscription' => [
                'endpoint' => 'https://fcm.googleapis.com/test',
                'keys' => ['p256dh' => 'test', 'auth' => 'test'],
            ],
            'last_seen_at' => now(),
        ]);

        // Mark parent as online
        $notificationService = app(NotificationService::class);
        $notificationService->markOnline($this->parent->id);

        /** @var Message $message */
        $message = Message::forceCreate([
            'school_id' => $this->school->id,
            'sender_id' => $this->admin->id,
            'transaction_id' => (string) Str::ulid(),
            'type' => 'announcement',
            'body' => 'Hello',
            'requires_read_receipt' => false,
            'sent_at' => now(),
        ]);

        $notificationService->notifyRecipient($message, $this->parent->id);

        Queue::assertNotPushed(SendPushNotificationJob::class);
    }

    public function test_sms_job_dispatched_with_delay_for_unread_receipt_message(): void
    {
        Queue::fake();

        /** @var Message $message */
        $message = Message::forceCreate([
            'school_id' => $this->school->id,
            'sender_id' => $this->admin->id,
            'transaction_id' => (string) Str::ulid(),
            'type' => 'attendance_alert',
            'body' => 'Your child is absent',
            'requires_read_receipt' => true,
            'sent_at' => now(),
        ]);

        $service = app(NotificationService::class);
        $service->notifyRecipient($message, $this->parent->id);

        Queue::assertPushed(PromoteToSmsJob::class);
    }

    public function test_sms_suppressed_when_sms_fallback_disabled(): void
    {
        Queue::fake();

        $this->school->update([
            'notification_settings' => ['sms_fallback_enabled' => false],
        ]);

        /** @var Message $message */
        $message = Message::forceCreate([
            'school_id' => $this->school->id,
            'sender_id' => $this->admin->id,
            'transaction_id' => (string) Str::ulid(),
            'type' => 'attendance_alert',
            'body' => 'Your child is absent',
            'requires_read_receipt' => true,
            'sent_at' => now(),
        ]);

        $service = app(NotificationService::class);
        $service->notifyRecipient($message, $this->parent->id);

        Queue::assertNotPushed(PromoteToSmsJob::class);
    }

    public function test_promote_to_sms_job_aborts_if_message_already_read(): void
    {
        /** @var Message $message */
        $message = Message::forceCreate([
            'school_id' => $this->school->id,
            'sender_id' => $this->admin->id,
            'transaction_id' => (string) Str::ulid(),
            'type' => 'attendance_alert',
            'body' => 'Your child is absent',
            'requires_read_receipt' => true,
            'sent_at' => now(),
        ]);

        \App\Models\MessageRecipient::forceCreate([
            'school_id' => $this->school->id,
            'message_id' => $message->id,
            'recipient_id' => $this->parent->id,
            'read_at' => now(), // already read
        ]);

        $logSpy = \Illuminate\Support\Facades\Log::spy();

        $job = new PromoteToSmsJob($message->id, $this->parent->id, $this->school->id);
        $job->handle(app(\App\Services\SmsService::class));

        // SmsService::send() logs 'SMS dispatch' when it sends — should NOT appear
        $logSpy->shouldNotHaveReceived('info', [\Mockery::pattern('/SMS dispatch/'), \Mockery::any()]);
    }

    public function test_markOnline_sets_cache_key(): void
    {
        $service = app(NotificationService::class);

        $this->assertFalse($service->isOnline($this->parent->id));

        $service->markOnline($this->parent->id);

        $this->assertTrue($service->isOnline($this->parent->id));
    }
}
