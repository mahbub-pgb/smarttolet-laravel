<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Listing;
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
        $this->shareAdminPendingCount();
        $this->shareNavigationPages();
        $this->shareEngagementState();
    }

    /**
     * Share the signed-in user's favourite listing ids (for the ❤️ toggle state
     * on cards) and their unread-notification count (header bell) with the
     * public layout and the views that render listing cards.
     */
    private function shareEngagementState(): void
    {
        View::composer(
            ['home', 'listings.index', 'listings.show', 'dashboard.index', 'dashboard.saved'],
            function ($view) {
                $user = auth('web')->user();
                $view->with('favoriteIds', $user
                    ? app(\App\Services\Engagement\FavoriteService::class)->ids($user)
                    : []);
            },
        );

        View::composer('layouts.app', function ($view) {
            $user = auth('web')->user();
            $view->with('webUnreadCount', $user
                ? app(\App\Services\Notification\NotificationService::class)->unreadCount($user)
                : 0);
        });
    }

    /**
     * Share the admin-managed static pages flagged for the header / footer with
     * the public layout, so their nav links render on every page.
     */
    private function shareNavigationPages(): void
    {
        View::composer('layouts.app', function ($view) {
            $pages = app(\App\Services\Page\PageService::class)->navigationPages();
            $view->with('headerPages', $pages->where('show_in_header', true));
            $view->with('footerPages', $pages->where('show_in_footer', true));
        });
    }

    /**
     * Show the admin how many listings are awaiting review — surfaced as a
     * notification badge in the admin layout (links to the pending list).
     */
    private function shareAdminPendingCount(): void
    {
        View::composer('admin.layout', function ($view) {
            $view->with(
                'pendingListingCount',
                Listing::query()->where('status', Listing::STATUS_PENDING)->count(),
            );
        });
    }

    /**
     * Share the admin-configured map zoom levels with every view that renders a
     * map, so the JS can read them off the map element's data attributes.
     */
    private function shareMapZoom(): void
    {
        View::composer(
            ['dashboard.form', 'listings.show', 'listings.map'],
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
