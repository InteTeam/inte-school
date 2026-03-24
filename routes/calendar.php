<?php

declare(strict_types=1);

use App\Http\Controllers\School\CalendarController;
use App\Http\Controllers\School\CalendarEventController;
use Illuminate\Support\Facades\Route;

$schoolMiddleware = ['auth', 'not_disabled', 'school', 'legal'];

// --- Admin / Teacher calendar ---
Route::middleware([...$schoolMiddleware, 'role:admin,teacher'])
    ->group(function (): void {
        Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
        Route::post('/calendar', [CalendarController::class, 'store'])->name('calendar.store');
        Route::delete('/calendar/{calendar}', [CalendarController::class, 'destroy'])->name('calendar.destroy');

        Route::post('/calendar/events', [CalendarEventController::class, 'store'])->name('calendar.events.store');
        Route::put('/calendar/events/{calendarEvent}', [CalendarEventController::class, 'update'])->name('calendar.events.update');
        Route::delete('/calendar/events/{calendarEvent}', [CalendarEventController::class, 'destroy'])->name('calendar.events.destroy');
    });

// --- Parent / Student external calendar (read-only) ---
Route::middleware([...$schoolMiddleware, 'role:parent,student'])
    ->group(function (): void {
        Route::get('/parent/calendar', [CalendarEventController::class, 'externalIndex'])->name('parent.calendar.index');
    });
