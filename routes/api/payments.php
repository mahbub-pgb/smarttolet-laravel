<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\PaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('payments')->group(function () {
    Route::get('/plans', [PaymentController::class, 'plans']);

    Route::middleware('jwt.auth')->group(function () {
        Route::get('/subscription', [PaymentController::class, 'subscription']);
        Route::post('/initiate', [PaymentController::class, 'initiate']);
        Route::post('/verify', [PaymentController::class, 'verify']);
    });
});
