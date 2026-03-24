<?php

declare(strict_types=1);

namespace Tests\Feature\Push;

use App\Jobs\SendPushNotificationJob;
use App\Models\RegisteredDevice;
use App\Models\School;
use App\Models\User;
use App\Services\VapidPushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class VapidPushServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->rootAdmin()->create();
        $this->school = School::factory()->create();
    }

    private function makeDevice(array $subscription = []): RegisteredDevice
    {
        $default = [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint',
            'keys' => [
                'p256dh' => 'BCVxsr7N_eNgVRqvHtD0zTZsEc6-VV-JvLexhqUzORcx6oV5y3IoK38tpL4a0ab9B3GiqaumSilhu3t-HgJin1A',
                'auth' => 'DGv6ra1nlYgDCS1FRnbzlw',
            ],
        ];

        return RegisteredDevice::forceCreate([
            'school_id' => $this->school->id,
            'user_id' => $this->user->id,
            'device_name' => 'Test Device',
            'device_fingerprint' => 'test-fp-' . uniqid(),
            'push_subscription' => $subscription ?: $default,
            'last_seen_at' => now(),
        ]);
    }

    public function test_push_skipped_when_no_subscription(): void
    {
        $device = RegisteredDevice::forceCreate([
            'school_id' => $this->school->id,
            'user_id' => $this->user->id,
            'device_name' => 'No Sub Device',
            'device_fingerprint' => 'fp-no-sub',
            'push_subscription' => null,
            'last_seen_at' => now(),
        ]);

        $service = app(VapidPushService::class);
        $result = $service->send($device, ['title' => 'Test']);

        $this->assertFalse($result);
    }

    public function test_push_skipped_when_vapid_keys_not_configured(): void
    {
        config(['app.vapid_public_key' => '', 'app.vapid_private_key' => '']);

        $device = $this->makeDevice();
        $service = app(VapidPushService::class);
        $result = $service->send($device, ['title' => 'Test']);

        $this->assertFalse($result);
    }

    public function test_push_returns_false_gracefully_when_service_unreachable(): void
    {
        // Provide fake VAPID keys — WebPush will fail to connect but should not throw
        config([
            'app.vapid_public_key' => 'BEl62iUYgUivxIkv69yViEuiBIa-Ib9-SkvMeAtA3LFgDzkrxZJjSgSnfckjBJuBkr3qBUYIHBQFLXYp5Nksh8U',
            'app.vapid_private_key' => 'tUfXq3tkLD4mbP9C_1IOAA',
        ]);

        $device = $this->makeDevice([
            'endpoint' => 'https://127.0.0.1:1/unreachable-endpoint',
            'keys' => [
                'p256dh' => 'BCVxsr7N_eNgVRqvHtD0zTZsEc6-VV-JvLexhqUzORcx6oV5y3IoK38tpL4a0ab9B3GiqaumSilhu3t-HgJin1A',
                'auth' => 'DGv6ra1nlYgDCS1FRnbzlw',
            ],
        ]);

        $service = app(VapidPushService::class);

        // Must return false, must not throw
        $result = $service->send($device, ['title' => 'Test']);
        $this->assertFalse($result);
    }

    public function test_send_push_notification_job_dispatches_to_high_queue(): void
    {
        Queue::fake();

        $device = $this->makeDevice();

        SendPushNotificationJob::dispatch($device, ['title' => 'Hello', 'body' => 'World']);

        Queue::assertPushedOn('high', SendPushNotificationJob::class);
    }

    public function test_send_push_notification_job_handles_failure_gracefully(): void
    {
        // The job's failed() method must not rethrow
        $device = $this->makeDevice();

        $job = new SendPushNotificationJob($device, ['title' => 'Test']);

        // failed() should swallow the exception
        $job->failed(new \RuntimeException('Push service down'));

        $this->assertTrue(true); // No exception = pass
    }
}
