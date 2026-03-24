<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Notifications\SmsProviderDownNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

final class SmsService
{
    /**
     * Send an SMS message. Never throws — logs and returns false on failure.
     * MVP: stub implementation (no provider configured). Returns true as if sent.
     */
    public function send(string $phoneNumber, string $body): bool
    {
        try {
            // TODO P3+: integrate Twilio / SNS / Vonage provider
            Log::info('SMS dispatch — no provider configured (MVP stub)', [
                'to' => $phoneNumber,
                'body_preview' => Str::limit($body, 50),
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('SMS send failed', [
                'to' => $phoneNumber,
                'error' => $e->getMessage(),
            ]);

            $this->alertRootAdmin($e->getMessage());

            return false;
        }
    }

    private function alertRootAdmin(string $errorMessage): void
    {
        try {
            $rootAdmin = User::where('is_root_admin', true)->first();

            if ($rootAdmin === null) {
                return;
            }

            Notification::send($rootAdmin, new SmsProviderDownNotification($errorMessage));
        } catch (\Throwable) {
            Log::error('Could not alert root admin of SMS provider failure');
        }
    }
}
