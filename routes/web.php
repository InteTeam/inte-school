<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\DeviceRegistrationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Parent\DashboardController as ParentDashboardController;
use App\Http\Controllers\RootAdmin\DashboardController as RootAdminDashboardController;
use App\Http\Controllers\RootAdmin\FeatureRequestController as RootAdminFeatureRequestController;
use App\Http\Controllers\RootAdmin\SchoolController as RootAdminSchoolController;
use App\Http\Controllers\School\LegalDocumentController;
use App\Http\Controllers\School\OnboardingController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Support\DashboardController as SupportDashboardController;
use App\Http\Controllers\Teacher\DashboardController as TeacherDashboardController;
use Illuminate\Support\Facades\Route;

// --- Landing page (public) ---
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return response()->view('welcome');
})->name('welcome');

// --- Auth ---
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

    Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.update');

    Route::get('/two-factor-challenge', [TwoFactorController::class, 'create'])->name('two-factor.challenge');
    Route::post('/two-factor-challenge', [TwoFactorController::class, 'store']);
});

Route::middleware(['auth', 'not_disabled'])->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/device-registration', [DeviceRegistrationController::class, 'create'])->name('device-registration');
    Route::post('/device-registration', [DeviceRegistrationController::class, 'store']);

    Route::post('/session/heartbeat', function () {
        return response()->json([
            'csrf_token' => csrf_token(),
            'session_lifetime' => config('session.lifetime'),
            'timestamp' => now()->toIso8601String(),
        ]);
    })->name('session.heartbeat');

    // Dashboard — redirects to role-specific dashboard
    Route::get('/dashboard', function () {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $role = $user->currentSchoolRole();

        return match ($role) {
            'admin' => redirect()->route('admin.dashboard'),
            'teacher' => redirect()->route('teacher.dashboard'),
            'parent' => redirect()->route('parent.dashboard'),
            'student' => redirect()->route('student.dashboard'),
            'support' => redirect()->route('support.dashboard'),
            default => inertia('Dashboard'), // root admin without school context
        };
    })->name('dashboard');
});

$schoolMiddleware = ['auth', 'not_disabled', 'school', 'legal'];

// --- Admin routes ---
Route::prefix('admin')->middleware([...$schoolMiddleware, 'role:admin'])->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
});

// --- Teacher routes ---
Route::prefix('teacher')->middleware([...$schoolMiddleware, 'role:teacher'])->group(function () {
    Route::get('/dashboard', [TeacherDashboardController::class, 'index'])->name('teacher.dashboard');
});

// --- Parent routes ---
Route::prefix('parent')->middleware([...$schoolMiddleware, 'role:parent'])->group(function () {
    Route::get('/dashboard', [ParentDashboardController::class, 'index'])->name('parent.dashboard');
});

// --- Student routes ---
Route::prefix('student')->middleware([...$schoolMiddleware, 'role:student'])->group(function () {
    Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('student.dashboard');
});

// --- Support routes ---
Route::prefix('support')->middleware([...$schoolMiddleware, 'role:support'])->group(function () {
    Route::get('/dashboard', [SupportDashboardController::class, 'index'])->name('support.dashboard');
});

// --- Onboarding (root admin or first-time setup) ---
Route::middleware(['auth', 'not_disabled'])->prefix('onboarding')->group(function () {
    Route::get('/step-1', [OnboardingController::class, 'step1'])->name('onboarding.step1');
    Route::post('/step-1', [OnboardingController::class, 'storeStep1']);
    Route::get('/step-2', [OnboardingController::class, 'step2'])->name('onboarding.step2');
    Route::post('/step-2', [OnboardingController::class, 'storeStep2']);
    Route::get('/step-3', [OnboardingController::class, 'step3'])->name('onboarding.step3');
    Route::post('/step-3', [OnboardingController::class, 'storeStep3']);
    Route::get('/step-4', [OnboardingController::class, 'step4'])->name('onboarding.step4');
    Route::post('/complete', [OnboardingController::class, 'complete'])->name('onboarding.complete');
});

// --- Legal documents ---
Route::middleware(['auth', 'not_disabled'])->group(function () {
    Route::get('/legal/accept', [LegalDocumentController::class, 'showAcceptance'])->name('legal.accept.show');
    Route::post('/legal/accept', [LegalDocumentController::class, 'recordAcceptance'])->name('legal.accept.store');
    Route::get('/legal/{type}', [LegalDocumentController::class, 'show'])->name('legal.show');
});

Route::middleware(['auth', 'not_disabled', 'school'])->group(function () {
    Route::get('/legal/{document}/edit', [LegalDocumentController::class, 'edit'])->name('legal.edit');
    Route::match(['PUT', 'POST'], '/legal/{document}', [LegalDocumentController::class, 'update'])->name('legal.update');
    Route::post('/legal/{document}/publish', [LegalDocumentController::class, 'publish'])->name('legal.publish');
});

// --- Root Admin ---
Route::prefix('root-admin')->middleware(['auth', 'not_disabled', 'root_admin'])->group(function () {
    Route::get('/', [RootAdminDashboardController::class, 'index'])->name('root-admin.dashboard');
    Route::get('/schools', [RootAdminSchoolController::class, 'index'])->name('root-admin.schools.index');
    Route::get('/feature-requests', [RootAdminFeatureRequestController::class, 'index'])->name('root-admin.feature-requests.index');
    Route::patch('/feature-requests/{featureRequest}/status', [RootAdminFeatureRequestController::class, 'updateStatus'])->name('root-admin.feature-requests.update-status');
});
