<?php

declare(strict_types=1);

use App\Http\Middleware\AuthenticateApiKey;
use App\Http\Middleware\CheckRootAdmin;
use App\Http\Middleware\EnsureLegalAcceptance;
use App\Http\Middleware\EnsureSchoolContext;
use App\Http\Middleware\EnsureUserIsNotDisabled;
use App\Http\Middleware\FeatureGate;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function (): void {
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/users.php'));
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/settings.php'));
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/messaging.php'));
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/attendance.php'));
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/calendar.php'));
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/tasks.php'));
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/documents.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR |
                     Request::HEADER_X_FORWARDED_HOST |
                     Request::HEADER_X_FORWARDED_PORT |
                     Request::HEADER_X_FORWARDED_PROTO |
                     Request::HEADER_X_FORWARDED_AWS_ELB,
        );

        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            'school' => EnsureSchoolContext::class,
            'root_admin' => CheckRootAdmin::class,
            'not_disabled' => EnsureUserIsNotDisabled::class,
            'feature' => FeatureGate::class,
            'legal' => EnsureLegalAcceptance::class,
            'role' => RoleMiddleware::class,
            'api_key' => AuthenticateApiKey::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
