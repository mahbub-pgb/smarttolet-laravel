<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\ChatController;
use Illuminate\Support\Facades\Route;

Route::prefix('chat')->middleware('jwt.auth')->group(function () {
    Route::get('/conversations', [ChatController::class, 'index']);
    Route::post('/conversations', [ChatController::class, 'store']);
    Route::get('/conversations/{id}/messages', [ChatController::class, 'messages'])->whereNumber('id');
    Route::post('/conversations/{id}/messages', [ChatController::class, 'send'])->whereNumber('id');
    Route::post('/conversations/{id}/read', [ChatController::class, 'read'])->whereNumber('id');
    Route::post('/conversations/{id}/typing', [ChatController::class, 'typing'])->whereNumber('id');
});
