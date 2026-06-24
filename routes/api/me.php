<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\MeController;
use Illuminate\Support\Facades\Route;

Route::prefix('me')->middleware('jwt.auth')->group(function () {
    // Favorites
    Route::get('/favorites', [MeController::class, 'favorites']);
    Route::post('/favorites', [MeController::class, 'addFavorite']);
    Route::delete('/favorites/{listingId}', [MeController::class, 'removeFavorite'])
        ->whereNumber('listingId');

    // Saved searches
    Route::get('/saved-searches', [MeController::class, 'savedSearches']);
    Route::post('/saved-searches', [MeController::class, 'createSavedSearch']);
    Route::delete('/saved-searches/{id}', [MeController::class, 'deleteSavedSearch'])
        ->whereNumber('id');

    // Notifications
    Route::get('/notifications', [MeController::class, 'notifications']);
    Route::patch('/notifications/read-all', [MeController::class, 'readAllNotifications']);
    Route::patch('/notifications/{id}/read', [MeController::class, 'readNotification'])
        ->whereNumber('id');
});
