<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\BlogController;
use Illuminate\Support\Facades\Route;

Route::prefix('blog')->group(function () {
    // Public reads.
    Route::get('/posts', [BlogController::class, 'index']);
    Route::get('/categories', [BlogController::class, 'categories']);
    Route::get('/posts/{slug}', [BlogController::class, 'show']);

    // Staff authoring (manage_blog).
    Route::middleware(['jwt.auth', 'permission:manage_blog'])->group(function () {
        Route::post('/posts', [BlogController::class, 'store']);
        Route::put('/posts/{post}', [BlogController::class, 'update']);
        Route::delete('/posts/{post}', [BlogController::class, 'destroy']);
    });
});
