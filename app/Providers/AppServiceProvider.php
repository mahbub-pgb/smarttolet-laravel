<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Settings\SettingsService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
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

        // Server-rendered web UI pagination uses a custom view themed by
        // public/css/app.css. (The built-in Bootstrap 5 view relies on
        // Bootstrap utility classes this Tailwind app doesn't ship, which
        // left two pagination bars rendering on top of each other.)
        Paginator::defaultView('vendor.pagination.app');
        Paginator::defaultSimpleView('vendor.pagination.app-simple');

        $this->shareMapZoom();
    }

    /**
     * Share the admin-configured map zoom levels with every view that renders a
     * map, so the JS can read them off the map element's data attributes.
     */
    private function shareMapZoom(): void
    {
        View::composer(
            ['dashboard.form', 'dashboard.profile', 'listings.show', 'listings.map'],
            function ($view) {
                $settings = app(SettingsService::class);
                $view->with('mapDefaultZoom', (int) $settings->get('map_default_zoom', 12));
                $view->with('mapPinnedZoom', (int) $settings->get('map_pinned_zoom', 16));
                $view->with('mapDefaultLat', (float) $settings->get('map_default_lat', 23.8103));
                $view->with('mapDefaultLng', (float) $settings->get('map_default_lng', 90.4125));
            },
        );
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
