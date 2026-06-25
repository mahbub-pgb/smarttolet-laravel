<?php

declare(strict_types=1);

use App\Http\Controllers\Web\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Web\Admin\ListingController as AdminListingController;
use App\Http\Controllers\Web\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Web\Auth\AuthController;
use App\Http\Controllers\Web\DashboardListingController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\ListingController;
use App\Http\Controllers\Web\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes (session-based, server-rendered Blade UI)
|--------------------------------------------------------------------------
|
| Public pages for browsing content, listings and a map; a phone+OTP signup
| flow; and a session-protected admin dashboard. The REST API lives in
| routes/api.php and is unaffected by these routes.
|
*/

// --- Public content ------------------------------------------------------
Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/listings', [ListingController::class, 'index'])->name('listings.index');
Route::get('/map', [ListingController::class, 'map'])->name('listings.map');
Route::get('/listings/{slug}', [ListingController::class, 'show'])->name('listings.show');

// --- Guest auth ----------------------------------------------------------
Route::middleware('guest:web')->group(function () {
    // Login.
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    // Signup — step 1: phone number -> send OTP via SMS.
    Route::get('/register', [AuthController::class, 'showRegisterPhone'])->name('register');
    Route::post('/register', [AuthController::class, 'sendOtp'])->middleware('throttle:6,1');

    // Signup — step 2: verify the OTP.
    Route::get('/register/verify', [AuthController::class, 'showVerify'])->name('register.verify');
    Route::post('/register/verify', [AuthController::class, 'verifyOtp']);
    Route::post('/register/resend', [AuthController::class, 'resendOtp'])
        ->name('register.resend')->middleware('throttle:6,1');

    // Signup — step 3: name, email, password.
    Route::get('/register/complete', [AuthController::class, 'showComplete'])->name('register.complete');
    Route::post('/register/complete', [AuthController::class, 'complete']);
});

// --- Authenticated user --------------------------------------------------
Route::middleware('auth:web')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // "My Listings" tab + create/edit/delete (owner only).
    Route::get('/dashboard', [DashboardListingController::class, 'index'])->name('dashboard');

    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('/listings/create', [DashboardListingController::class, 'create'])->name('listings.create');
        Route::post('/listings', [DashboardListingController::class, 'store'])->name('listings.store');
        Route::get('/listings/{listing}/edit', [DashboardListingController::class, 'edit'])->name('listings.edit');
        Route::put('/listings/{listing}', [DashboardListingController::class, 'update'])->name('listings.update');
        Route::delete('/listings/{listing}', [DashboardListingController::class, 'destroy'])->name('listings.destroy');

        // "Profile Settings" tab.
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    });
});

// --- Admin area (super admin + admin) ------------------------------------
Route::middleware(['auth:web', 'web.role:admin,super_admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        // Manage all user listings: approve / unpublish to draft / delete.
        Route::get('/listings', [AdminListingController::class, 'index'])->name('listings.index');
        Route::post('/listings/{listing}/approve', [AdminListingController::class, 'approve'])->name('listings.approve');
        Route::post('/listings/{listing}/draft', [AdminListingController::class, 'draft'])->name('listings.draft');
        Route::delete('/listings/{listing}', [AdminListingController::class, 'destroy'])->name('listings.destroy');

        Route::get('/settings/sms', [AdminSettingsController::class, 'sms'])->name('settings.sms');
        Route::post('/settings/sms', [AdminSettingsController::class, 'updateSms'])->name('settings.sms.update');
        Route::post('/settings/sms/test', [AdminSettingsController::class, 'testSms'])->name('settings.sms.test');
    });
