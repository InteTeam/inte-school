<?php

declare(strict_types=1);

namespace App\Services;

use Alphagov\Notifications\Client as NotifyClient;
use App\Models\SmsLog;
use App\Models\User;
use App\Notifications\SmsProviderDownNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

final class SmsService
{
    private ?NotifyClient $client = null;

    /**
     * Allow injection of a mock client for testing.
     */
    public function setClient(NotifyClient $client): void
    {
        $this->client = $client;
    }

    /**
     * Send an SMS via GOV.UK Notify. Never throws — logs and returns false on failure.
     */
    public function send(string $phoneNumber, string $body, ?string $schoolId = null, ?string $recipientId = null, ?string $messageId = null): bool
    {
        $apiKey = config('services.govuk_notify.api_key');
        $templateId = config('services.govuk_notify.sms_template_id');

        if (empty($apiKey) || empty($templateId)) {
            Log::warning('GOV.UK Notify not configured — SMS skipped', [
                'to' => $this->maskPhone($phoneNumber),
            ]);

            return false;
        }

        try {
            $client = $this->getClient($apiKey);
            $segments = $this->calculateSegments($body);

            $response = $client->sendSms(
                $phoneNumber,
                $templateId,
                ['body' => $body],
            );

            $notifyId = $response['id'] ?? null;

            if ($schoolId !== null && $recipientId !== null) {
                SmsLog::forceCreate([
                    'school_id' => $schoolId,
                    'recipient_id' => $recipientId,
                    'message_id' => $messageId,
                    'phone_number' => $this->maskPhone($phoneNumber),
                    'notify_message_id' => $notifyId,
                    'status' => 'queued',
                    'segments' => $segments,
                    'cost_pence' => $this->calculateCost($schoolId, $segments),
                    'sent_at' => now(),
                ]);
            }

            Log::info('SMS sent via GOV.UK Notify', [
                'to' => $this->maskPhone($phoneNumber),
                'notify_id' => $notifyId,
                'segments' => $segments,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('SMS send failed via GOV.UK Notify', [
                'to' => $this->maskPhone($phoneNumber),
                'error' => $e->getMessage(),
            ]);

            if ($schoolId !== null && $recipientId !== null) {
                SmsLog::forceCreate([
                    'school_id' => $schoolId,
                    'recipient_id' => $recipientId,
                    'message_id' => $messageId,
                    'phone_number' => $this->maskPhone($phoneNumber),
                    'status' => 'failed',
                    'segments' => 0,
                    'cost_pence' => 0,
                    'sent_at' => now(),
                    'failed_at' => now(),
                    'failure_reason' => $e->getMessage(),
                ]);
            }

            $this->alertRootAdmin($e->getMessage());

            return false;
        }
    }

    /**
     * Count SMS sent for a school since the current GOV.UK Notify allowance year (April 1).
     */
    public function getUsageThisYear(string $schoolId): int
    {
        return SmsLog::withoutGlobalScope(\App\Models\Scopes\SchoolScope::class)
            ->where('school_id', $schoolId)
            ->where('sent_at', '>=', $this->currentAllowanceStart())
            ->where('status', '!=', 'failed')
            ->sum('segments');
    }

    /**
     * Free texts remaining for the current allowance year.
     */
    public function getRemainingFreeTexts(string $schoolId): int
    {
        $allowance = (int) config('services.govuk_notify.free_allowance', 5000);

        return max(0, $allowance - $this->getUsageThisYear($schoolId));
    }

    /**
     * True if school has used >= threshold of the free allowance.
     */
    public function isApproachingLimit(string $schoolId, float $threshold = 0.8): bool
    {
        $allowance = (int) config('services.govuk_notify.free_allowance', 5000);
        $used = $this->getUsageThisYear($schoolId);

        return $used >= (int) ($allowance * $threshold);
    }

    /**
     * Calculate the number of SMS segments for a given body.
     * Standard SMS: 160 chars = 1 segment, 306 = 2, 459 = 3, etc.
     */
    public function calculateSegments(string $body): int
    {
        $length = mb_strlen($body);

        if ($length <= 160) {
            return 1;
        }

        // Concatenated messages: 153 usable chars per segment (7 reserved for UDH header)
        return (int) ceil($length / 153);
    }

    /**
     * Start of the current GOV.UK Notify free allowance year (April 1).
     */
    private function currentAllowanceStart(): Carbon
    {
        $now = now();

        if ($now->month >= 4) {
            return $now->copy()->setMonth(4)->setDay(1)->startOfDay();
        }

        return $now->copy()->subYear()->setMonth(4)->setDay(1)->startOfDay();
    }

    /**
     * Calculate cost in pence. Free within allowance, then per-segment rate.
     */
    private function calculateCost(string $schoolId, int $segments): int
    {
        $remaining = $this->getRemainingFreeTexts($schoolId);

        if ($remaining >= $segments) {
            return 0;
        }

        $billableSegments = $segments - $remaining;
        $costPerSegment = (int) config('services.govuk_notify.cost_pence', 3);

        return $billableSegments * $costPerSegment;
    }

    /**
     * Mask phone number for logging: +44***1234
     */
    private function maskPhone(string $phone): string
    {
        $len = strlen($phone);

        if ($len <= 4) {
            return $phone;
        }

        return substr($phone, 0, 3) . str_repeat('*', $len - 7) . substr($phone, -4);
    }

    private function getClient(string $apiKey): NotifyClient
    {
        if ($this->client === null) {
            $this->client = new NotifyClient([
                'apiKey' => $apiKey,
                'httpClient' => new \Http\Adapter\Guzzle7\Client(),
            ]);
        }

        return $this->client;
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
