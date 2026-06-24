<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    // Public auth endpoints — tightly rate limited (Redis-backed).
    Route::middleware('throttle:otp')->group(function () {
        Route::post('/otp/request', [AuthController::class, 'requestOtp']);
        Route::post('/otp/verify', [AuthController::class, 'verifyOtp']);
    });

    Route::middleware('throttle:auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
    });

    // Authenticated session endpoints.
    Route::middleware('jwt.auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);

        Route::middleware('throttle:otp')->group(function () {
            Route::post('/email/otp/request', [AuthController::class, 'requestEmailOtp']);
            Route::post('/email/otp/verify', [AuthController::class, 'verifyEmailOtp']);
        });
    });
});
