<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SmsProviderDownNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $errorMessage,
    ) {}

    /** @return array<int, string> */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[Inte-School] SMS provider failure')
            ->line('The SMS provider failed when attempting to deliver a notification.')
            ->line("Error: {$this->errorMessage}")
            ->line('Please check your SMS provider configuration.');
    }
}
