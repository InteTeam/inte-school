<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\MessageSent;
use App\Listeners\HandleMessageSent;
use App\Models\AttendanceRecord;
use App\Models\CalendarEvent;
use App\Models\School;
use App\Observers\AttendanceObserver;
use App\Observers\CalendarEventObserver;
use App\Observers\SchoolSettingsObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        School::observe(SchoolSettingsObserver::class);
        AttendanceRecord::observe(AttendanceObserver::class);
        CalendarEvent::observe(CalendarEventObserver::class);
        Event::listen(MessageSent::class, HandleMessageSent::class);
        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by((string) $request->user()?->id);
        });
    }
}
