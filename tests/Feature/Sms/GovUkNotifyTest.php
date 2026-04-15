<?php

declare(strict_types=1);

namespace Tests\Feature\Sms;

use Alphagov\Notifications\Client as NotifyClient;
use App\Jobs\PromoteToSmsJob;
use App\Models\Message;
use App\Models\MessageRecipient;
use App\Models\School;
use App\Models\Scopes\SchoolScope;
use App\Models\SmsLog;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

final class GovUkNotifyTest extends TestCase
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
                'sms_fallback_types' => ['attendance_alert', 'trip_permission'],
            ],
        ]);

        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
        $this->school->users()->attach($this->admin->id, [
            'id' => Str::ulid(), 'role' => 'admin',
            'accepted_at' => now(), 'invited_at' => now(),
        ]);

        $this->parent = User::factory()->create([
            'email' => 'parent@example.com',
            'phone' => '+447700900123',
        ]);
        $this->school->users()->attach($this->parent->id, [
            'id' => Str::ulid(), 'role' => 'parent',
            'accepted_at' => now(), 'invited_at' => now(),
        ]);

        config([
            'services.govuk_notify.api_key' => 'test-key-for-testing',
            'services.govuk_notify.sms_template_id' => 'template-id-for-testing',
            'services.govuk_notify.free_allowance' => 5000,
            'services.govuk_notify.cost_pence' => 3,
        ]);
    }

    private function mockNotifyClient(array $response = []): NotifyClient
    {
        $client = Mockery::mock(NotifyClient::class);
        $client->shouldReceive('sendSms')
            ->andReturn(array_merge(['id' => 'notify-msg-' . Str::random(8)], $response));

        return $client;
    }

    private function failingNotifyClient(string $message = 'API error'): NotifyClient
    {
        $client = Mockery::mock(NotifyClient::class);
        $client->shouldReceive('sendSms')
            ->andThrow(new \RuntimeException($message));

        return $client;
    }

    // --- SmsService: successful send ---

    public function test_send_creates_sms_log_on_success(): void
    {
        $service = app(SmsService::class);
        $service->setClient($this->mockNotifyClient());

        $result = $service->send(
            '+447700900123',
            'Test message',
            $this->school->id,
            $this->parent->id,
        );

        $this->assertTrue($result);

        $log = SmsLog::withoutGlobalScope(SchoolScope::class)
            ->where('school_id', $this->school->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('queued', $log->status);
        $this->assertSame($this->parent->id, $log->recipient_id);
        $this->assertNotNull($log->notify_message_id);
        $this->assertNotNull($log->sent_at);
    }

    public function test_send_masks_phone_in_log(): void
    {
        $service = app(SmsService::class);
        $service->setClient($this->mockNotifyClient());

        $service->send('+447700900123', 'Test', $this->school->id, $this->parent->id);

        $log = SmsLog::withoutGlobalScope(SchoolScope::class)->first();

        // Phone should be masked: +44***0123
        $this->assertStringNotContainsString('7700900', $log->phone_number);
        $this->assertStringEndsWith('0123', $log->phone_number);
    }

    // --- SmsService: failed send ---

    public function test_send_creates_failed_log_on_api_error(): void
    {
        $service = app(SmsService::class);
        $service->setClient($this->failingNotifyClient('Invalid phone number'));

        $result = $service->send(
            '+447700900123',
            'Test message',
            $this->school->id,
            $this->parent->id,
        );

        $this->assertFalse($result);

        $log = SmsLog::withoutGlobalScope(SchoolScope::class)->first();

        $this->assertNotNull($log);
        $this->assertSame('failed', $log->status);
        $this->assertNotNull($log->failed_at);
        $this->assertSame('Invalid phone number', $log->failure_reason);
    }

    public function test_send_returns_false_when_not_configured(): void
    {
        config(['services.govuk_notify.api_key' => null]);

        $service = app(SmsService::class);

        $result = $service->send('+447700900123', 'Test');

        $this->assertFalse($result);
    }

    public function test_send_never_throws(): void
    {
        $service = app(SmsService::class);
        $service->setClient($this->failingNotifyClient('Total disaster'));

        // Should return false, not throw
        $result = $service->send('+447700900123', 'Test', $this->school->id, $this->parent->id);

        $this->assertFalse($result);
    }

    // --- Segment calculation ---

    public function test_short_message_is_one_segment(): void
    {
        $service = app(SmsService::class);

        $this->assertSame(1, $service->calculateSegments('Hello'));
        $this->assertSame(1, $service->calculateSegments(str_repeat('a', 160)));
    }

    public function test_long_message_calculates_segments_correctly(): void
    {
        $service = app(SmsService::class);

        // 161 chars = 2 segments (153 usable per concatenated segment)
        $this->assertSame(2, $service->calculateSegments(str_repeat('a', 161)));
        // 306 chars = 2 segments
        $this->assertSame(2, $service->calculateSegments(str_repeat('a', 306)));
        // 307 chars = 3 segments
        $this->assertSame(3, $service->calculateSegments(str_repeat('a', 307)));
    }

    // --- Usage tracking ---

    public function test_usage_counts_successful_sends_this_year(): void
    {
        SmsLog::forceCreate([
            'school_id' => $this->school->id,
            'recipient_id' => $this->parent->id,
            'phone_number' => '+44***0123',
            'status' => 'queued',
            'segments' => 2,
            'cost_pence' => 0,
            'sent_at' => now(),
        ]);
        SmsLog::forceCreate([
            'school_id' => $this->school->id,
            'recipient_id' => $this->parent->id,
            'phone_number' => '+44***0123',
            'status' => 'delivered',
            'segments' => 1,
            'cost_pence' => 0,
            'sent_at' => now(),
        ]);

        $service = app(SmsService::class);

        $this->assertSame(3, $service->getUsageThisYear($this->school->id));
    }

    public function test_usage_excludes_failed_sends(): void
    {
        SmsLog::forceCreate([
            'school_id' => $this->school->id,
            'recipient_id' => $this->parent->id,
            'phone_number' => '+44***0123',
            'status' => 'queued',
            'segments' => 1,
            'cost_pence' => 0,
            'sent_at' => now(),
        ]);
        SmsLog::forceCreate([
            'school_id' => $this->school->id,
            'recipient_id' => $this->parent->id,
            'phone_number' => '+44***0123',
            'status' => 'failed',
            'segments' => 1,
            'cost_pence' => 0,
            'sent_at' => now(),
            'failed_at' => now(),
        ]);

        $service = app(SmsService::class);

        $this->assertSame(1, $service->getUsageThisYear($this->school->id));
    }

    public function test_remaining_free_texts_calculated_correctly(): void
    {
        SmsLog::forceCreate([
            'school_id' => $this->school->id,
            'recipient_id' => $this->parent->id,
            'phone_number' => '+44***0123',
            'status' => 'queued',
            'segments' => 100,
            'cost_pence' => 0,
            'sent_at' => now(),
        ]);

        $service = app(SmsService::class);

        $this->assertSame(4900, $service->getRemainingFreeTexts($this->school->id));
    }

    public function test_remaining_never_goes_negative(): void
    {
        SmsLog::forceCreate([
            'school_id' => $this->school->id,
            'recipient_id' => $this->parent->id,
            'phone_number' => '+44***0123',
            'status' => 'queued',
            'segments' => 6000,
            'cost_pence' => 3000,
            'sent_at' => now(),
        ]);

        $service = app(SmsService::class);

        $this->assertSame(0, $service->getRemainingFreeTexts($this->school->id));
    }

    public function test_approaching_limit_at_80_percent(): void
    {
        SmsLog::forceCreate([
            'school_id' => $this->school->id,
            'recipient_id' => $this->parent->id,
            'phone_number' => '+44***0123',
            'status' => 'queued',
            'segments' => 4000,
            'cost_pence' => 0,
            'sent_at' => now(),
        ]);

        $service = app(SmsService::class);

        $this->assertTrue($service->isApproachingLimit($this->school->id));
    }

    public function test_not_approaching_limit_under_threshold(): void
    {
        SmsLog::forceCreate([
            'school_id' => $this->school->id,
            'recipient_id' => $this->parent->id,
            'phone_number' => '+44***0123',
            'status' => 'queued',
            'segments' => 100,
            'cost_pence' => 0,
            'sent_at' => now(),
        ]);

        $service = app(SmsService::class);

        $this->assertFalse($service->isApproachingLimit($this->school->id));
    }

    // --- Multi-tenant isolation ---

    public function test_usage_scoped_to_school(): void
    {
        $otherSchool = School::factory()->create();

        SmsLog::forceCreate([
            'school_id' => $this->school->id,
            'recipient_id' => $this->parent->id,
            'phone_number' => '+44***0123',
            'status' => 'queued',
            'segments' => 100,
            'cost_pence' => 0,
            'sent_at' => now(),
        ]);
        SmsLog::forceCreate([
            'school_id' => $otherSchool->id,
            'recipient_id' => $this->parent->id,
            'phone_number' => '+44***0123',
            'status' => 'queued',
            'segments' => 200,
            'cost_pence' => 0,
            'sent_at' => now(),
        ]);

        $service = app(SmsService::class);

        $this->assertSame(100, $service->getUsageThisYear($this->school->id));
        $this->assertSame(200, $service->getUsageThisYear($otherSchool->id));
    }

    // --- Cost calculation ---

    public function test_within_free_allowance_costs_zero(): void
    {
        $service = app(SmsService::class);
        $service->setClient($this->mockNotifyClient());

        $service->send('+447700900123', 'Free text', $this->school->id, $this->parent->id);

        $log = SmsLog::withoutGlobalScope(SchoolScope::class)->first();
        $this->assertSame(0, $log->cost_pence);
    }

    // --- PromoteToSmsJob: type check ---

    public function test_job_sends_for_allowed_type(): void
    {
        $message = Message::forceCreate([
            'school_id' => $this->school->id,
            'sender_id' => $this->admin->id,
            'transaction_id' => (string) Str::ulid(),
            'type' => 'attendance_alert',
            'body' => 'Your child is absent',
            'requires_read_receipt' => true,
            'sent_at' => now(),
        ]);

        MessageRecipient::forceCreate([
            'school_id' => $this->school->id,
            'message_id' => $message->id,
            'recipient_id' => $this->parent->id,
        ]);

        $service = app(SmsService::class);
        $service->setClient($this->mockNotifyClient());
        $this->app->instance(SmsService::class, $service);

        $job = new PromoteToSmsJob($message->id, $this->parent->id, $this->school->id);
        $job->handle($service);

        $this->assertSame(1, SmsLog::withoutGlobalScope(SchoolScope::class)->count());
    }

    public function test_job_skips_disallowed_type(): void
    {
        $message = Message::forceCreate([
            'school_id' => $this->school->id,
            'sender_id' => $this->admin->id,
            'transaction_id' => (string) Str::ulid(),
            'type' => 'announcement', // NOT in sms_fallback_types
            'body' => 'Sports day is Friday',
            'requires_read_receipt' => false,
            'sent_at' => now(),
        ]);

        MessageRecipient::forceCreate([
            'school_id' => $this->school->id,
            'message_id' => $message->id,
            'recipient_id' => $this->parent->id,
        ]);

        $service = app(SmsService::class);
        $job = new PromoteToSmsJob($message->id, $this->parent->id, $this->school->id);
        $job->handle($service);

        $this->assertSame(0, SmsLog::withoutGlobalScope(SchoolScope::class)->count());
    }

    public function test_job_skips_when_already_read(): void
    {
        $message = Message::forceCreate([
            'school_id' => $this->school->id,
            'sender_id' => $this->admin->id,
            'transaction_id' => (string) Str::ulid(),
            'type' => 'attendance_alert',
            'body' => 'Absent',
            'requires_read_receipt' => true,
            'sent_at' => now(),
        ]);

        MessageRecipient::forceCreate([
            'school_id' => $this->school->id,
            'message_id' => $message->id,
            'recipient_id' => $this->parent->id,
            'read_at' => now(), // Already read
        ]);

        $service = app(SmsService::class);
        $job = new PromoteToSmsJob($message->id, $this->parent->id, $this->school->id);
        $job->handle($service);

        $this->assertSame(0, SmsLog::withoutGlobalScope(SchoolScope::class)->count());
    }

    public function test_job_skips_when_sms_fallback_disabled(): void
    {
        $this->school->update([
            'notification_settings' => ['sms_fallback_enabled' => false],
        ]);

        $message = Message::forceCreate([
            'school_id' => $this->school->id,
            'sender_id' => $this->admin->id,
            'transaction_id' => (string) Str::ulid(),
            'type' => 'attendance_alert',
            'body' => 'Absent',
            'requires_read_receipt' => true,
            'sent_at' => now(),
        ]);

        MessageRecipient::forceCreate([
            'school_id' => $this->school->id,
            'message_id' => $message->id,
            'recipient_id' => $this->parent->id,
        ]);

        $service = app(SmsService::class);
        $job = new PromoteToSmsJob($message->id, $this->parent->id, $this->school->id);
        $job->handle($service);

        $this->assertSame(0, SmsLog::withoutGlobalScope(SchoolScope::class)->count());
    }

    public function test_job_skips_when_user_has_no_phone(): void
    {
        $this->parent->update(['phone' => null]);

        $message = Message::forceCreate([
            'school_id' => $this->school->id,
            'sender_id' => $this->admin->id,
            'transaction_id' => (string) Str::ulid(),
            'type' => 'attendance_alert',
            'body' => 'Absent',
            'requires_read_receipt' => true,
            'sent_at' => now(),
        ]);

        MessageRecipient::forceCreate([
            'school_id' => $this->school->id,
            'message_id' => $message->id,
            'recipient_id' => $this->parent->id,
        ]);

        $service = app(SmsService::class);
        $job = new PromoteToSmsJob($message->id, $this->parent->id, $this->school->id);
        $job->handle($service);

        $this->assertSame(0, SmsLog::withoutGlobalScope(SchoolScope::class)->count());
    }
}
