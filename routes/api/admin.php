<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Admin\AdvertisementController;
use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Admin\ListingModerationController;
use App\Http\Controllers\Api\V1\Admin\PaymentLedgerController;
use App\Http\Controllers\Api\V1\Admin\ReportController;
use App\Http\Controllers\Api\V1\Admin\UserController;
use Illuminate\Support\Facades\Route;

// All admin routes require authentication; individual groups add granular
// permission guards. (Settings live in routes/api/settings.php.)
Route::prefix('admin')->middleware('jwt.auth')->group(function () {
    // Dashboard / analytics
    Route::middleware('permission:view_analytics')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/dashboard/charts', [DashboardController::class, 'charts']);
    });

    // User & staff management (rank-guarded in the service layer)
    Route::middleware('permission:manage_users')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{user}', [UserController::class, 'show'])->whereNumber('user');
        Route::patch('/users/{user}', [UserController::class, 'update'])->whereNumber('user');
    });
    Route::delete('/users/{user}', [UserController::class, 'destroy'])
        ->whereNumber('user')
        ->middleware('permission:delete_accounts');

    // Listing moderation
    Route::middleware('permission:review_listings')->group(function () {
        Route::get('/listings/queue', [ListingModerationController::class, 'queue']);
        Route::patch('/listings/{listing}/moderate', [ListingModerationController::class, 'moderate']);
    });

    // Reports
    Route::middleware('permission:manage_reports')->group(function () {
        Route::get('/reports', [ReportController::class, 'index']);
        Route::patch('/reports/{report}', [ReportController::class, 'resolve'])
            ->whereNumber('report')
            ->middleware('permission:resolve_reports');
    });

    // Advertisements
    Route::middleware('permission:manage_advertisements')->group(function () {
        Route::get('/advertisements', [AdvertisementController::class, 'index']);
        Route::post('/advertisements', [AdvertisementController::class, 'store']);
        Route::put('/advertisements/{advertisement}', [AdvertisementController::class, 'update'])->whereNumber('advertisement');
        Route::delete('/advertisements/{advertisement}', [AdvertisementController::class, 'destroy'])->whereNumber('advertisement');
    });

    // Payment ledger
    Route::get('/payments', [PaymentLedgerController::class, 'index'])
        ->middleware('permission:manage_payments');
});
