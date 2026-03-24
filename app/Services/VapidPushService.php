<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\RegisteredDevice;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

final class VapidPushService
{
    /** @param array<string, mixed> $payload */
    public function send(RegisteredDevice $device, array $payload): bool
    {
        /** @var array<string, mixed>|null $subscription */
        $subscription = $device->push_subscription;

        if (empty($subscription) || empty($subscription['endpoint'])) {
            Log::debug('Push skipped — no subscription', ['device_id' => $device->id]);

            return false;
        }

        $vapidPublicKey = config('app.vapid_public_key');
        $vapidPrivateKey = config('app.vapid_private_key');

        if (empty($vapidPublicKey) || empty($vapidPrivateKey)) {
            Log::warning('Push skipped — VAPID keys not configured');

            return false;
        }

        try {
            $webPush = new WebPush([
                'VAPID' => [
                    'subject' => config('app.url'),
                    'publicKey' => $vapidPublicKey,
                    'privateKey' => $vapidPrivateKey,
                ],
            ]);

            $sub = Subscription::create($this->buildSubscription($subscription));
            $webPush->queueNotification($sub, json_encode($payload));

            foreach ($webPush->flush() as $report) {
                if (! $report->isSuccess()) {
                    Log::warning('Push notification failed', [
                        'device_id' => $device->id,
                        'reason' => $report->getReason(),
                    ]);

                    return false;
                }
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('Push notification exception — graceful return', [
                'device_id' => $device->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Normalise the stored subscription array to the shape WebPush expects.
     *
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    private function buildSubscription(array $raw): array
    {
        $keys = $raw['keys'] ?? [];

        return [
            'endpoint' => $raw['endpoint'],
            'contentEncoding' => $raw['contentEncoding'] ?? 'aesgcm',
            'keys' => [
                'p256dh' => $keys['p256dh'] ?? '',
                'auth' => $keys['auth'] ?? '',
            ],
        ];
    }
}
