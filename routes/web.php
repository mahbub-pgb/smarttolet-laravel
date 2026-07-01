<?php

declare(strict_types=1);

use App\Http\Controllers\Web\Admin\BlogController as AdminBlogController;
use App\Http\Controllers\Web\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Web\Admin\ListingController as AdminListingController;
use App\Http\Controllers\Web\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Web\Auth\AuthController;
use App\Http\Controllers\Web\Auth\PasswordResetController;
use App\Http\Controllers\Web\BlogController;
use App\Http\Controllers\Web\DashboardListingController;
use App\Http\Controllers\Web\EngagementController;
use App\Http\Controllers\Web\MediaController;
use App\Http\Controllers\Web\NotificationController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\ListingController;
use App\Http\Controllers\Web\PageController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\Admin\PageController as AdminPageController;
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

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

Route::get('/pages/{slug}', [PageController::class, 'show'])->name('pages.show');

// --- Guest auth ----------------------------------------------------------
Route::middleware('guest:web')->group(function () {
    // Login.
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    // Forgot password — recover via SMS OTP (rate-limited so codes can't be spammed).
    Route::get('/password/forgot', [PasswordResetController::class, 'showForgot'])->name('password.forgot');
    Route::post('/password/forgot', [PasswordResetController::class, 'sendOtp'])->middleware('throttle:password-reset');
    Route::get('/password/verify', [PasswordResetController::class, 'showVerify'])->name('password.verify');
    Route::post('/password/verify', [PasswordResetController::class, 'verifyOtp'])->middleware('throttle:10,1');
    Route::post('/password/resend', [PasswordResetController::class, 'resendOtp'])
        ->name('password.resend')->middleware('throttle:6,1');
    Route::get('/password/reset', [PasswordResetController::class, 'showReset'])->name('password.reset');
    Route::post('/password/reset', [PasswordResetController::class, 'reset'])->middleware('throttle:10,1');

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

    // Reveal (and remember) a listing owner's phone number.
    Route::post('/listings/{listing}/reveal-contact', [ListingController::class, 'revealContact'])
        ->name('listings.reveal-contact');

    // Favourite / un-favourite a listing (AJAX from the ❤️ on cards).
    Route::post('/listings/{listing}/favorite', [EngagementController::class, 'toggleFavorite'])
        ->name('favorites.toggle');

    // Notifications feed (saved-search matches, listing approvals, etc.).
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');

    // "My Listings" tab + create/edit/delete (owner only).
    Route::get('/dashboard', [DashboardListingController::class, 'index'])->name('dashboard');

    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('/listings/create', [DashboardListingController::class, 'create'])->name('listings.create');
        Route::post('/listings', [DashboardListingController::class, 'store'])->name('listings.store');
        Route::get('/listings/{listing}/edit', [DashboardListingController::class, 'edit'])->name('listings.edit');
        Route::put('/listings/{listing}', [DashboardListingController::class, 'update'])->name('listings.update');
        Route::delete('/listings/{listing}', [DashboardListingController::class, 'destroy'])->name('listings.destroy');

        // "Analytics" tab — views + phone reveals across the user's listings.
        Route::get('/analytics', [DashboardListingController::class, 'analytics'])->name('analytics');

        // "Saved" tab — favourited listings.
        Route::get('/saved', [EngagementController::class, 'saved'])->name('saved');

        // "Searches" tab — build + manage saved searches (with alerts).
        Route::get('/searches', [EngagementController::class, 'searches'])->name('searches');
        Route::post('/saved-searches', [EngagementController::class, 'storeSearch'])->name('searches.store');
        Route::delete('/saved-searches/{search}', [EngagementController::class, 'destroySearch'])->name('searches.destroy');

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
        Route::get('/listings/{listing}/preview', [AdminListingController::class, 'preview'])->name('listings.preview');
        Route::post('/listings/{listing}/approve', [AdminListingController::class, 'approve'])->name('listings.approve');
        Route::post('/listings/{listing}/reject', [AdminListingController::class, 'reject'])->name('listings.reject');
        Route::post('/listings/{listing}/draft', [AdminListingController::class, 'draft'])->name('listings.draft');
        Route::delete('/listings/{listing}', [AdminListingController::class, 'destroy'])->name('listings.destroy');

        Route::get('/settings/maps', [AdminSettingsController::class, 'maps'])->name('settings.maps');
        Route::post('/settings/maps', [AdminSettingsController::class, 'updateMaps'])->name('settings.maps.update');

        Route::get('/settings/sms', [AdminSettingsController::class, 'sms'])->name('settings.sms');
        Route::post('/settings/sms', [AdminSettingsController::class, 'updateSms'])->name('settings.sms.update');
        Route::post('/settings/sms/test', [AdminSettingsController::class, 'testSms'])->name('settings.sms.test');
    });

// --- Blog management (moderator/editor + above, guarded on manage_blog) ----
Route::middleware(['auth:web', 'web.permission:manage_blog'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/blog', [AdminBlogController::class, 'index'])->name('blog.index');
        Route::get('/blog/create', [AdminBlogController::class, 'create'])->name('blog.create');
        Route::post('/blog', [AdminBlogController::class, 'store'])->name('blog.store');
        Route::post('/blog/upload-image', [AdminBlogController::class, 'uploadImage'])->name('blog.upload');

        // Central media library (list existing / upload new) for the picker.
        Route::get('/media', [MediaController::class, 'index'])->name('media.index');
        Route::post('/media', [MediaController::class, 'store'])->name('media.store');
        Route::get('/blog/{post}/edit', [AdminBlogController::class, 'edit'])->name('blog.edit');
        Route::put('/blog/{post}', [AdminBlogController::class, 'update'])->name('blog.update');
        Route::delete('/blog/{post}', [AdminBlogController::class, 'destroy'])->name('blog.destroy');
    });

// --- Static page management (guarded on manage_pages) --------------------
Route::middleware(['auth:web', 'web.permission:manage_pages'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/pages', [AdminPageController::class, 'index'])->name('pages.index');
        Route::get('/pages/create', [AdminPageController::class, 'create'])->name('pages.create');
        Route::post('/pages', [AdminPageController::class, 'store'])->name('pages.store');
        Route::post('/pages/upload-image', [AdminPageController::class, 'uploadImage'])->name('pages.upload');
        Route::get('/pages/{page}/edit', [AdminPageController::class, 'edit'])->name('pages.edit');
        Route::put('/pages/{page}', [AdminPageController::class, 'update'])->name('pages.update');
        Route::delete('/pages/{page}', [AdminPageController::class, 'destroy'])->name('pages.destroy');
    });
