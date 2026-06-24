<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureRateLimiters();
    }

    /**
     * Redis-backed rate limiters: a generous global limiter plus tight
     * limiters on auth and OTP routes.
     */
    private function configureRateLimiters(): void
    {
        // Global API limiter (applied to the `api` middleware group below).
        RateLimiter::for('api', function (Request $request) {
            $key = $request->user()?->id ?: $request->ip();

            return [Limit::perMinute(120)->by((string) $key)];
        });

        // Tight limiter for login / token refresh.
        RateLimiter::for('auth', function (Request $request) {
            return [
                Limit::perMinute(10)->by($request->ip()),
                Limit::perMinute(5)->by('id:'.(string) $request->input('identifier', 'anon')),
            ];
        });

        // Very tight limiter for OTP request / verify (per IP + per mobile).
        RateLimiter::for('otp', function (Request $request) {
            $target = (string) ($request->input('mobile') ?? $request->input('email') ?? $request->user()?->id ?? 'anon');

            return [
                Limit::perMinute(5)->by($request->ip()),
                Limit::perMinutes(60, 10)->by('otp:'.$target),
            ];
        });
    }
}
