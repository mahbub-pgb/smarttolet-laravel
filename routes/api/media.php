<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\MediaController;
use Illuminate\Support\Facades\Route;

Route::prefix('media')->middleware('jwt.auth')->group(function () {
    Route::get('/', [MediaController::class, 'index']);
    Route::post('/', [MediaController::class, 'store']);
    Route::delete('/{media}', [MediaController::class, 'destroy'])->whereNumber('media');
});
