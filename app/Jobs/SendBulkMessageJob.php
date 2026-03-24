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

class SendBulkMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $messageData
     * @param  array<int, string>    $recipientIds
     */
    public function __construct(
        private readonly School $school,
        private readonly User $sender,
        private readonly array $messageData,
        private readonly array $recipientIds,
    ) {
        $this->onQueue('default');
    }

    public function handle(MessagingService $messagingService): void
    {
        foreach (array_chunk($this->recipientIds, 50) as $chunk) {
            try {
                $messagingService->send(
                    $this->school,
                    $this->sender,
                    $this->messageData,
                    $chunk,
                );
            } catch (\Throwable $e) {
                Log::warning('Bulk message chunk failed', [
                    'school_id' => $this->school->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendBulkMessageJob permanently failed', [
            'school_id' => $this->school->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
