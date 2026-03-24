<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Notifications\MailProviderDownNotification;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

final class MailService
{
    private string $primaryMailer;

    private string $fallbackMailer;

    public function __construct()
    {
        $this->primaryMailer = config('mail.default', 'resend');
        $this->fallbackMailer = config('mail.fallback_mailer', 'log');
    }

    /**
     * Send a mailable, falling back to secondary mailer on primary failure.
     * Never throws — logs and returns gracefully on total failure.
     *
     * @param string|array<int, string> $to
     */
    public function send(string|array $to, Mailable $mailable): void
    {
        try {
            Mail::mailer($this->primaryMailer)->to($to)->send($mailable);
        } catch (\Throwable $primary) {
            Log::warning('Primary mailer failed, switching to fallback', [
                'mailer' => $this->primaryMailer,
                'error' => $primary->getMessage(),
            ]);

            $this->alertRootAdmin($primary->getMessage());

            try {
                Mail::mailer($this->fallbackMailer)->to($to)->send($mailable);
            } catch (\Throwable $fallback) {
                Log::error('Fallback mailer also failed', [
                    'mailer' => $this->fallbackMailer,
                    'error' => $fallback->getMessage(),
                ]);
                // Graceful return — do not rethrow
            }
        }
    }

    /**
     * Send a raw notification directly (e.g. password reset), with the same fallback logic.
     *
     * @param string|array<int, string> $to
     */
    public function sendNotification(string|array $to, \Illuminate\Notifications\Messages\MailMessage $message): void
    {
        // For simple notifications, wrap in a generic mailable and delegate
        // This method is a convenience hook; most callers use send() with a full Mailable
        $this->sendRaw($to, $message->subject, (string) $message->render());
    }

    /**
     * Send a raw HTML email string.
     *
     * @param string|array<int, string> $to
     */
    public function sendRaw(string|array $to, string $subject, string $htmlBody): void
    {
        $mailable = new \App\Mail\RawMailable($subject, $htmlBody);
        $this->send($to, $mailable);
    }

    private function alertRootAdmin(string $errorMessage): void
    {
        try {
            $rootAdmin = User::where('is_root_admin', true)->first();

            if ($rootAdmin === null) {
                return;
            }

            Notification::send(
                $rootAdmin,
                new MailProviderDownNotification(
                    $this->primaryMailer,
                    $this->fallbackMailer,
                    $errorMessage,
                )
            );
        } catch (\Throwable) {
            // Cannot alert if mail is down — log only
            Log::error('Could not alert root admin of mail provider failure');
        }
    }
}
