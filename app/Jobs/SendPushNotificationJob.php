<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\RegisteredDevice;
use App\Services\VapidPushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @param array<string, mixed> $payload */
    public function __construct(
        private readonly RegisteredDevice $device,
        private readonly array $payload,
    ) {
        $this->onQueue('high');
    }

    public function handle(VapidPushService $pushService): void
    {
        // Failure is handled gracefully inside VapidPushService — no throws
        $pushService->send($this->device, $this->payload);
    }

    public function failed(\Throwable $exception): void
    {
        // Job-level failure: log and swallow — push notifications are best-effort
        \Illuminate\Support\Facades\Log::warning('SendPushNotificationJob permanently failed', [
            'device_id' => $this->device->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
