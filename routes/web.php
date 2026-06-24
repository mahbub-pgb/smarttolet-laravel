<?php

declare(strict_types=1);

use App\Http\Controllers\Web\Auth\AuthController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\ListingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes (session-based, server-rendered Blade UI)
|--------------------------------------------------------------------------
|
| Public-facing pages for browsing content and listings, a map view, and
| session auth (login / register). The REST API lives in routes/api.php and
| is unaffected by these routes.
|
*/

// --- Public content ------------------------------------------------------
Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/listings', [ListingController::class, 'index'])->name('listings.index');
Route::get('/map', [ListingController::class, 'map'])->name('listings.map');
Route::get('/listings/{slug}', [ListingController::class, 'show'])->name('listings.show');

// --- Guest auth ----------------------------------------------------------
Route::middleware('guest:web')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// --- Authenticated -------------------------------------------------------
Route::middleware('auth:web')->group(function () {
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
