<?php

declare(strict_types=1);

use App\Http\Controllers\School\DocumentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Document Routes
|--------------------------------------------------------------------------
*/

// Admin / Teacher: upload, list, delete documents
Route::middleware(['auth', 'school', 'not_disabled', 'legal'])
    ->group(function (): void {
        Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
        Route::get('/documents/upload', [DocumentController::class, 'create'])->name('documents.create');
        Route::match(['POST', 'PUT'], '/documents', [DocumentController::class, 'store'])->name('documents.store');
        Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    });

// RAG query — parents and students (feature:rag gate)
Route::middleware(['auth', 'school', 'not_disabled', 'legal', 'feature:rag'])
    ->group(function (): void {
        Route::post('/ask', [DocumentController::class, 'query'])->name('documents.query');
    });
