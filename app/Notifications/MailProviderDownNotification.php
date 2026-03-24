<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MailProviderDownNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $failedMailer,
        private readonly string $fallbackMailer,
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
            ->subject('[Inte-School] Mail provider down — fallback active')
            ->line("Primary mailer **{$this->failedMailer}** failed and has been switched to **{$this->fallbackMailer}**.")
            ->line("Error: {$this->errorMessage}")
            ->line('Please check your mail provider configuration.');
    }
}
