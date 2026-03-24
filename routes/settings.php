<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ApiKeyController;
use App\Http\Controllers\Admin\FeatureRequestController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\StatisticsController;
use Illuminate\Support\Facades\Route;

$adminMiddleware = ['auth', 'not_disabled', 'school', 'legal', 'role:admin'];

Route::prefix('admin/settings')->middleware($adminMiddleware)->group(function (): void {
    Route::get('/general', [SettingsController::class, 'general'])->name('admin.settings.general');
    Route::match(['PUT', 'POST'], '/general', [SettingsController::class, 'updateGeneral'])->name('admin.settings.general.update');

    Route::get('/notifications', [SettingsController::class, 'notifications'])->name('admin.settings.notifications');
    Route::put('/notifications', [SettingsController::class, 'updateNotifications'])->name('admin.settings.notifications.update');

    Route::get('/security', [SettingsController::class, 'security'])->name('admin.settings.security');
    Route::put('/security', [SettingsController::class, 'updateSecurity'])->name('admin.settings.security.update');

    Route::get('/legal', [SettingsController::class, 'legal'])->name('admin.settings.legal');

    // API key management
    Route::get('/api-keys', [ApiKeyController::class, 'index'])->name('admin.settings.api-keys');
    Route::post('/api-keys', [ApiKeyController::class, 'store'])->name('admin.settings.api-keys.store');
    Route::delete('/api-keys/{schoolApiKey}', [ApiKeyController::class, 'destroy'])->name('admin.settings.api-keys.destroy');

    // Feature requests
    Route::get('/feature-requests', [FeatureRequestController::class, 'index'])->name('admin.settings.feature-requests');
    Route::post('/feature-requests', [FeatureRequestController::class, 'store'])->name('admin.settings.feature-requests.store');
});

// Statistics dashboard
Route::prefix('admin')->middleware($adminMiddleware)->group(function (): void {
    Route::get('/statistics', [StatisticsController::class, 'index'])->name('admin.statistics');
});
