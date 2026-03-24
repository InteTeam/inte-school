<?php

declare(strict_types=1);

namespace Tests\Feature\Mail;

use App\Mail\RawMailable;
use App\Models\User;
use App\Notifications\MailProviderDownNotification;
use App\Services\MailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class MailServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_sends_via_primary_mailer(): void
    {
        Mail::fake();

        $service = app(MailService::class);
        $service->sendRaw('user@example.com', 'Test Subject', '<p>Hello</p>');

        Mail::assertSent(RawMailable::class, fn ($mail) => $mail->hasTo('user@example.com'));
    }

    public function test_falls_back_to_secondary_mailer_on_primary_failure(): void
    {
        // Configure primary to fail, fallback to array (always succeeds)
        config([
            'mail.default' => 'resend',
            'mail.fallback_mailer' => 'array',
        ]);

        Mail::shouldReceive('mailer')
            ->once()
            ->with('resend')
            ->andReturnSelf();

        Mail::shouldReceive('to')
            ->once()
            ->andReturnSelf();

        Mail::shouldReceive('send')
            ->once()
            ->andThrow(new \RuntimeException('Resend API unreachable'));

        // Fallback mailer should be used
        Mail::shouldReceive('mailer')
            ->once()
            ->with('array')
            ->andReturnSelf();

        Mail::shouldReceive('to')
            ->once()
            ->andReturnSelf();

        Mail::shouldReceive('send')
            ->once()
            ->andReturn(null);

        $service = app(MailService::class);
        // Should not throw
        $service->sendRaw('user@example.com', 'Test', '<p>Body</p>');
    }

    public function test_root_admin_is_alerted_when_primary_mailer_fails(): void
    {
        Notification::fake();

        User::factory()->rootAdmin()->create(['email' => 'root@example.com']);

        config([
            'mail.default' => 'resend',
            'mail.fallback_mailer' => 'array',
        ]);

        Mail::shouldReceive('mailer')
            ->with('resend')
            ->andReturnSelf();

        Mail::shouldReceive('to')
            ->andReturnSelf();

        Mail::shouldReceive('send')
            ->once()
            ->andThrow(new \RuntimeException('Connection refused'));

        Mail::shouldReceive('mailer')
            ->with('array')
            ->andReturnSelf();

        Mail::shouldReceive('send')
            ->once()
            ->andReturn(null);

        $service = app(MailService::class);
        $service->sendRaw('user@example.com', 'Test', '<p>Body</p>');

        Notification::assertSentTo(
            User::where('is_root_admin', true)->first(),
            MailProviderDownNotification::class,
        );
    }

    public function test_no_exception_thrown_when_both_mailers_fail(): void
    {
        Mail::shouldReceive('mailer')->andReturnSelf();
        Mail::shouldReceive('to')->andReturnSelf();
        Mail::shouldReceive('send')->andThrow(new \RuntimeException('All providers down'));

        Notification::fake(); // suppress root admin notification side-effects

        $service = app(MailService::class);

        // Must not throw
        $this->expectNotToPerformAssertions();
        $service->sendRaw('user@example.com', 'Test', '<p>Body</p>');
    }

    public function test_graceful_return_when_no_root_admin_exists(): void
    {
        // No root admin created — alertRootAdmin should not crash
        Mail::shouldReceive('mailer')->andReturnSelf();
        Mail::shouldReceive('to')->andReturnSelf();
        Mail::shouldReceive('send')->andThrow(new \RuntimeException('Failure'));

        Notification::fake();

        $service = app(MailService::class);
        $service->sendRaw('user@example.com', 'Test', '<p>Body</p>');

        Notification::assertNothingSent();
    }
}
