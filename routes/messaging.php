<?php

declare(strict_types=1);

use App\Http\Controllers\School\MessageController;
use Illuminate\Support\Facades\Route;

$schoolMiddleware = ['auth', 'not_disabled', 'school', 'legal'];

Route::middleware($schoolMiddleware)->group(function () {
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::post('/messages', [MessageController::class, 'send'])->name('messages.send');
    Route::get('/messages/{message}', [MessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/{message}/read', [MessageController::class, 'markRead'])->name('messages.read');
    Route::post('/messages/{message}/reply', [MessageController::class, 'quickReply'])->name('messages.reply');
    Route::get('/messages/attachments/{attachment}/download', [MessageController::class, 'downloadAttachment'])->name('messages.attachments.download');
});
