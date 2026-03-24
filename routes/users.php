<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ClassController;
use App\Http\Controllers\Admin\GuardianController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Auth\AcceptInvitationController;
use Illuminate\Support\Facades\Route;

// --- Invitation acceptance (guests) ---
Route::middleware('guest')->group(function () {
    Route::get('/invitation/accept', [AcceptInvitationController::class, 'create'])->name('invitation.accept');
    Route::post('/invitation/accept', [AcceptInvitationController::class, 'store']);
});

$adminMiddleware = ['auth', 'not_disabled', 'school', 'legal', 'role:admin'];

// --- Admin: Staff management ---
Route::prefix('admin/staff')->middleware($adminMiddleware)->group(function () {
    Route::get('/', [StaffController::class, 'index'])->name('admin.staff.index');
    Route::post('/invite', [StaffController::class, 'invite'])->name('admin.staff.invite');
});

// --- Admin: Student management ---
Route::prefix('admin/students')->middleware($adminMiddleware)->group(function () {
    Route::get('/', [StudentController::class, 'index'])->name('admin.students.index');
    Route::post('/enrol', [StudentController::class, 'enrol'])->name('admin.students.enrol');
    Route::post('/import', [StudentController::class, 'import'])->name('admin.students.import');
    Route::get('/export-template', [StudentController::class, 'exportTemplate'])->name('admin.students.export-template');
});

// --- Admin: Guardian management ---
Route::prefix('admin/guardians')->middleware($adminMiddleware)->group(function () {
    Route::get('/', [GuardianController::class, 'index'])->name('admin.guardians.index');
    Route::post('/generate-code', [GuardianController::class, 'generateCode'])->name('admin.guardians.generate-code');
    Route::post('/link', [GuardianController::class, 'link'])->name('admin.guardians.link');
});

// --- Admin: Class management ---
Route::prefix('admin/classes')->middleware($adminMiddleware)->group(function () {
    Route::get('/', [ClassController::class, 'index'])->name('admin.classes.index');
    Route::post('/', [ClassController::class, 'store'])->name('admin.classes.store');
    Route::get('/{class}', [ClassController::class, 'show'])->name('admin.classes.show');
    Route::match(['PUT', 'POST'], '/{class}', [ClassController::class, 'update'])->name('admin.classes.update');
    Route::delete('/{class}', [ClassController::class, 'destroy'])->name('admin.classes.destroy');
    Route::post('/{class}/students/{student}', [ClassController::class, 'addStudent'])->name('admin.classes.add-student');
    Route::delete('/{class}/students/{student}', [ClassController::class, 'removeStudent'])->name('admin.classes.remove-student');
});
