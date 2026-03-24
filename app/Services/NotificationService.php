<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\PromoteToSmsJob;
use App\Jobs\SendPushNotificationJob;
use App\Models\Message;
use App\Models\RegisteredDevice;
use App\Models\School;
use App\Models\Scopes\SchoolScope;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class NotificationService
{
    /**
     * Trigger the notification cascade for a single recipient:
     *   1. Reverb broadcast is handled by the MessageSent event (already fired)
     *   2. VAPID push — only if the user is NOT currently online
     *   3. SMS fallback — dispatched with delay if requires_read_receipt + sms_fallback_enabled
     */
    public function notifyRecipient(Message $message, string $recipientId): void
    {
        if (! $this->isOnline($recipientId)) {
            $this->dispatchPushNotifications($message, $recipientId);
        }

        if ($message->requires_read_receipt) {
            $this->dispatchSmsFallback($message, $recipientId);
        }
    }

    /**
     * Mark a user as online (called when the user establishes an Echo connection).
     * TTL of 120 s gives a 2-minute grace period after the last heartbeat.
     */
    public function markOnline(string $userId, int $ttlSeconds = 120): void
    {
        Cache::put("user:online:{$userId}", true, $ttlSeconds);
    }

    /**
     * Returns true if the user has an active Reverb connection (tracked via cache heartbeat).
     */
    public function isOnline(string $userId): bool
    {
        return Cache::has("user:online:{$userId}");
    }

    private function dispatchPushNotifications(Message $message, string $recipientId): void
    {
        $devices = RegisteredDevice::withoutGlobalScope(SchoolScope::class)
            ->where('user_id', $recipientId)
            ->get();

        foreach ($devices as $device) {
            /** @var array<string, mixed>|null $sub */
            $sub = $device->push_subscription;

            if (empty($sub) || empty($sub['endpoint'])) {
                continue;
            }

            SendPushNotificationJob::dispatch($device, [
                'title' => $message->sender->name ?? __('messages.school'),
                'body' => Str::limit($message->body, 100),
                'url' => '/messages/' . $message->id,
            ]);
        }
    }

    private function dispatchSmsFallback(Message $message, string $recipientId): void
    {
        $school = School::find($message->school_id);

        if ($school === null) {
            return;
        }

        if (! (bool) $school->getNotificationSetting('sms_fallback_enabled', false)) {
            return;
        }

        $timeout = (int) $school->getNotificationSetting('sms_timeout_seconds', 900);

        PromoteToSmsJob::dispatch($message->id, $recipientId, $message->school_id)
            ->delay(now()->addSeconds($timeout))
            ->onQueue('high');
    }
}
