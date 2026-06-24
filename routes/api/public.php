<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\PublicController;
use App\Http\Controllers\Api\V1\SettingsController;
use Illuminate\Support\Facades\Route;

Route::prefix('public')->group(function () {
    Route::get('/settings', [SettingsController::class, 'publicShow']);
    Route::get('/places/nearby', [PublicController::class, 'nearbyPlaces']);
    Route::get('/advertisements', [PublicController::class, 'advertisements']);
});
