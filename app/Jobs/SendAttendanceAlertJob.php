<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\School;
use App\Models\User;
use App\Services\MessagingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendAttendanceAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly string $schoolId,
        private readonly string $senderId,
        private readonly string $recipientId,
        private readonly string $studentName,
    ) {
        $this->onQueue('high');
    }

    public function handle(MessagingService $messagingService): void
    {
        $school = School::find($this->schoolId);
        $sender = User::find($this->senderId);

        if ($school === null || $sender === null) {
            return;
        }

        $messagingService->send(
            $school,
            $sender,
            [
                'type' => 'attendance_alert',
                'body' => __('attendance.absent_alert_body', ['name' => $this->studentName]),
            ],
            [$this->recipientId],
        );
    }

    public function failed(\Throwable $exception): void
    {
        Log::warning('SendAttendanceAlertJob permanently failed', [
            'school_id' => $this->schoolId,
            'recipient_id' => $this->recipientId,
            'error' => $exception->getMessage(),
        ]);
    }
}
