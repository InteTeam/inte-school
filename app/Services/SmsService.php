<?php

declare(strict_types=1);

namespace App\Services;

use Alphagov\Notifications\Client as NotifyClient;
use App\Models\School;
use App\Models\SmsLog;
use App\Models\User;
use App\Notifications\SmsProviderDownNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

final class SmsService
{
    /** @var array<string, NotifyClient> keyed by school_id or 'platform' */
    private array $clients = [];

    /**
     * Allow injection of a mock client for testing.
     */
    public function setClient(NotifyClient $client): void
    {
        $this->clients['__test__'] = $client;
    }

    /**
     * Send an SMS via GOV.UK Notify. Never throws — logs and returns false on failure.
     *
     * Resolves API key per-school first, falls back to platform config.
     */
    public function send(string $phoneNumber, string $body, ?string $schoolId = null, ?string $recipientId = null, ?string $messageId = null): bool
    {
        $credentials = $this->resolveCredentials($schoolId);

        if ($credentials === null) {
            Log::warning('GOV.UK Notify not configured — SMS skipped', [
                'school_id' => $schoolId,
                'to' => $this->maskPhone($phoneNumber),
            ]);

            return false;
        }

        try {
            $client = $this->getClient($credentials['api_key'], $schoolId);
            $segments = $this->calculateSegments($body);

            $response = $client->sendSms(
                $phoneNumber,
                $credentials['template_id'],
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
                'school_id' => $schoolId,
                'to' => $this->maskPhone($phoneNumber),
                'notify_id' => $notifyId,
                'segments' => $segments,
                'source' => $credentials['source'],
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('SMS send failed via GOV.UK Notify', [
                'school_id' => $schoolId,
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
     * Resolve Notify credentials: per-school first, platform config fallback.
     *
     * @return array{api_key: string, template_id: string, source: string}|null
     */
    public function resolveCredentials(?string $schoolId): ?array
    {
        // Per-school credentials (encrypted in notification_settings JSONB)
        if ($schoolId !== null) {
            $school = School::find($schoolId);

            if ($school !== null) {
                $encryptedKey = $school->getNotificationSetting('govuk_notify_api_key');
                $templateId = $school->getNotificationSetting('govuk_notify_template_id');

                if (! empty($encryptedKey) && ! empty($templateId)) {
                    try {
                        $apiKey = Crypt::decryptString($encryptedKey);

                        return [
                            'api_key' => $apiKey,
                            'template_id' => $templateId,
                            'source' => 'school',
                        ];
                    } catch (\Throwable $e) {
                        Log::error('Failed to decrypt school Notify API key', [
                            'school_id' => $schoolId,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        }

        // Platform-level fallback (from .env — for testing or schools without own key)
        $apiKey = config('services.govuk_notify.api_key');
        $templateId = config('services.govuk_notify.sms_template_id');

        if (! empty($apiKey) && ! empty($templateId)) {
            return [
                'api_key' => $apiKey,
                'template_id' => $templateId,
                'source' => 'platform',
            ];
        }

        return null;
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

    private function getClient(string $apiKey, ?string $schoolId = null): NotifyClient
    {
        // Test mock takes priority
        if (isset($this->clients['__test__'])) {
            return $this->clients['__test__'];
        }

        $cacheKey = $schoolId ?? 'platform';

        if (! isset($this->clients[$cacheKey])) {
            $this->clients[$cacheKey] = new NotifyClient([
                'apiKey' => $apiKey,
                'httpClient' => new \Http\Adapter\Guzzle7\Client(),
            ]);
        }

        return $this->clients[$cacheKey];
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
