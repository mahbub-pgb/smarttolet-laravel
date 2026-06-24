<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\SettingsController;
use Illuminate\Support\Facades\Route;

// Admin settings (also see routes/api/public.php for the public view).
Route::prefix('admin/settings')
    ->middleware(['jwt.auth', 'permission:manage_settings'])
    ->group(function () {
        Route::get('/', [SettingsController::class, 'adminIndex']);
        Route::put('/', [SettingsController::class, 'adminUpdate']);
    });
