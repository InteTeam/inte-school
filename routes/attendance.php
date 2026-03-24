<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Teacher\AttendanceController as TeacherAttendanceController;
use Illuminate\Support\Facades\Route;

$schoolMiddleware = ['auth', 'not_disabled', 'school', 'legal'];

// --- Teacher attendance ---
Route::prefix('teacher/attendance')
    ->middleware([...$schoolMiddleware, 'role:teacher'])
    ->group(function (): void {
        Route::get('/', [TeacherAttendanceController::class, 'index'])->name('teacher.attendance.index');
        Route::get('/register/{classId}', [TeacherAttendanceController::class, 'register'])->name('teacher.attendance.register');
        Route::post('/mark', [TeacherAttendanceController::class, 'mark'])->name('teacher.attendance.mark');
    });

// --- Admin attendance ---
Route::prefix('admin/attendance')
    ->middleware([...$schoolMiddleware, 'role:admin'])
    ->group(function (): void {
        Route::get('/', [AdminAttendanceController::class, 'index'])->name('admin.attendance.index');
        Route::post('/override', [AdminAttendanceController::class, 'override'])->name('admin.attendance.override');
    });
