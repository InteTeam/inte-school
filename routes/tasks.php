<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\TaskController as AdminTaskController;
use App\Http\Controllers\Teacher\TaskController as TeacherTaskController;
use Illuminate\Support\Facades\Route;

$schoolMiddleware = ['auth', 'not_disabled', 'school', 'legal'];

// --- Teacher tasks ---
Route::prefix('teacher/tasks')
    ->middleware([...$schoolMiddleware, 'role:teacher'])
    ->group(function (): void {
        Route::get('/', [TeacherTaskController::class, 'index'])->name('teacher.tasks.index');
        Route::post('/', [TeacherTaskController::class, 'store'])->name('teacher.tasks.store');
        Route::get('/homework/create', [TeacherTaskController::class, 'createHomework'])->name('teacher.tasks.homework.create');
        Route::post('/homework', [TeacherTaskController::class, 'storeHomework'])->name('teacher.tasks.homework.store');
        Route::post('/items/toggle', [TeacherTaskController::class, 'toggleItem'])->name('teacher.tasks.items.toggle');
        Route::post('/items/reorder', [TeacherTaskController::class, 'reorder'])->name('teacher.tasks.items.reorder');
    });

// --- Admin task template groups ---
Route::prefix('admin/tasks')
    ->middleware([...$schoolMiddleware, 'role:admin'])
    ->group(function (): void {
        Route::get('/template-groups', [AdminTaskController::class, 'templateGroupsIndex'])->name('admin.tasks.template-groups.index');
        Route::post('/template-groups', [AdminTaskController::class, 'storeTemplateGroup'])->name('admin.tasks.template-groups.store');
        Route::post('/apply-template', [AdminTaskController::class, 'applyTemplate'])->name('admin.tasks.apply-template');
    });
