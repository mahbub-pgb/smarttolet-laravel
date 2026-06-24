<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\ListingController;
use Illuminate\Support\Facades\Route;

Route::prefix('listings')->group(function () {
    // Public browse/search.
    Route::get('/', [ListingController::class, 'index']);

    // Authenticated writes (registered before the {idOrSlug} catch-all).
    Route::middleware(['jwt.auth', 'maintenance.gate'])->group(function () {
        Route::get('/me/list', [ListingController::class, 'mine']);
        Route::post('/', [ListingController::class, 'store']);
        Route::delete('/bulk', [ListingController::class, 'bulkDestroy']);
        Route::put('/{listing}', [ListingController::class, 'update']);
        Route::delete('/{listing}', [ListingController::class, 'destroy']);
        Route::post('/{listing}/renew', [ListingController::class, 'renew']);
        Route::patch('/{listing}/status', [ListingController::class, 'setStatus']);
        Route::post('/{listing}/report', [ListingController::class, 'report']);
    });

    // Public detail (optional auth resolves the viewer when a token is present).
    Route::get('/{idOrSlug}', [ListingController::class, 'show']);
    Route::get('/{idOrSlug}/nearby', [ListingController::class, 'nearby']);
    Route::post('/{listing}/contact', [ListingController::class, 'contact']);
});
