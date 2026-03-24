<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\MessageSent;
use App\Services\NotificationService;

class HandleMessageSent
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function handle(MessageSent $event): void
    {
        foreach ($event->recipientIds as $recipientId) {
            $this->notificationService->notifyRecipient($event->message, $recipientId);
        }
    }
}
