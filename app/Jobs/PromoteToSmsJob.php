<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\MessageRecipient;
use App\Models\Scopes\SchoolScope;
use App\Models\School;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PromoteToSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Only one attempt — SMS is best-effort and we do not want duplicate messages. */
    public int $tries = 1;

    public function __construct(
        private readonly string $messageId,
        private readonly string $recipientId,
        private readonly string $schoolId,
    ) {
        $this->onQueue('high');
    }

    public function handle(SmsService $smsService): void
    {
        // Abort if the user already read the message
        $recipient = MessageRecipient::withoutGlobalScope(SchoolScope::class)
            ->where('message_id', $this->messageId)
            ->where('recipient_id', $this->recipientId)
            ->first();

        if ($recipient?->read_at !== null) {
            return;
        }

        // Abort if school has disabled SMS fallback
        $school = School::find($this->schoolId);

        if ($school === null || ! (bool) $school->getNotificationSetting('sms_fallback_enabled', false)) {
            return;
        }

        $user = User::find($this->recipientId);

        if ($user === null || empty($user->phone)) {
            return;
        }

        $smsService->send($user->phone, __('messages.sms_fallback_body'));
    }

    public function failed(\Throwable $exception): void
    {
        Log::warning('PromoteToSmsJob permanently failed', [
            'message_id' => $this->messageId,
            'recipient_id' => $this->recipientId,
            'error' => $exception->getMessage(),
        ]);
    }
}
