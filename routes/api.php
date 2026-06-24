<?php

declare(strict_types=1);

use App\Support\ApiResponse;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (prefix: /api/v1)
|--------------------------------------------------------------------------
|
| The apiPrefix is configured in bootstrap/app.php as "api/v1". Module route
| files are required below to keep this file readable.
|
*/

Route::get('/health', function () {
    return ApiResponse::success([
        'status' => 'ok',
        'time' => now()->toIso8601String(),
        'service' => config('app.name'),
    ], 'Service healthy');
})->name('health');

require __DIR__.'/api/auth.php';
require __DIR__.'/api/settings.php';
require __DIR__.'/api/listings.php';
require __DIR__.'/api/me.php';
require __DIR__.'/api/chat.php';
require __DIR__.'/api/payments.php';
require __DIR__.'/api/blog.php';
require __DIR__.'/api/media.php';
require __DIR__.'/api/public.php';
require __DIR__.'/api/admin.php';
