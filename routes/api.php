<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AttendanceHardwareController;
use App\Http\Controllers\Api\StatsApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Stateless routes for external integrations (hardware devices, stats API).
| No web session or CSRF — authenticated via token per request.
*/

Route::prefix('v1')->group(function (): void {
    // Hardware NFC attendance scanner endpoint
    Route::post('/attendance/mark', AttendanceHardwareController::class)
        ->middleware('throttle:60,1')
        ->name('api.attendance.mark');

    // School statistics API — authenticated by API key, rate-limited 60/min/key
    Route::get('/stats/{schoolSlug}', [StatsApiController::class, 'index'])
        ->middleware(['api_key', 'throttle:60,1'])
        ->name('api.stats.index');
});
