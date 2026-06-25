<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Resolves application settings with precedence: DB settings row -> config/env
 * fallback. The resolved map is cached in Redis for 10 minutes and busted on
 * write. Secret fields are flagged so they can be masked/hidden from public
 * responses.
 */
class SettingsService
{
    private const CACHE_KEY = 'settings:resolved';

    private const CACHE_TTL = 600; // 10 minutes

    /**
     * Schema of known settings: key => [env-fallback resolver, is_secret].
     *
     * @return array<string, array{default: mixed, secret: bool}>
     */
    private function schema(): array
    {
        return [
            'site_name' => ['default' => config('app.name'), 'secret' => false],
            'logo' => ['default' => null, 'secret' => false],
            'support_email' => ['default' => env('SUPPORT_EMAIL'), 'secret' => false],
            'support_phone' => ['default' => env('SUPPORT_PHONE'), 'secret' => false],
            'maintenance_mode' => ['default' => false, 'secret' => false],

            // Google Maps: browser key is public (needed by the SDK); server key is secret.
            'google_maps_browser_key' => ['default' => config('geo.google.browser_key'), 'secret' => false],
            'google_maps_server_key' => ['default' => config('geo.google.server_key'), 'secret' => true],

            // Map zoom levels (0-22). "default" is used when no pin is set yet;
            // "pinned" is used once a location is chosen / shown on a listing.
            'map_default_zoom' => ['default' => (int) config('geo.map.default_zoom', 12), 'secret' => false],
            'map_pinned_zoom' => ['default' => (int) config('geo.map.pinned_zoom', 16), 'secret' => false],

            // Default map centre — where the browse map and empty pickers open.
            'map_default_lat' => ['default' => (float) config('geo.map.default_lat', 23.8103), 'secret' => false],
            'map_default_lng' => ['default' => (float) config('geo.map.default_lng', 90.4125), 'secret' => false],

            // SMS
            'sms_provider' => ['default' => config('sms.default'), 'secret' => false],
            'sms_sender_id' => ['default' => config('sms.sender_id'), 'secret' => false],
            'sms_api_key' => ['default' => config('sms.drivers.bulksmsbd.api_key'), 'secret' => true],

            // Cloudinary
            'cloudinary_cloud_name' => ['default' => env('CLOUDINARY_CLOUD_NAME'), 'secret' => false],
            'cloudinary_api_key' => ['default' => env('CLOUDINARY_API_KEY'), 'secret' => true],
            'cloudinary_api_secret' => ['default' => env('CLOUDINARY_API_SECRET'), 'secret' => true],
        ];
    }

    /**
     * Fully resolved settings (DB overrides env). Cached in Redis.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            $overrides = Setting::query()->pluck('value', 'key')->all();

            $resolved = [];
            foreach ($this->schema() as $key => $meta) {
                $resolved[$key] = array_key_exists($key, $overrides)
                    ? $this->unwrap($overrides[$key])
                    : $meta['default'];
            }

            return $resolved;
        });
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function isMaintenanceMode(): bool
    {
        return (bool) $this->get('maintenance_mode', false);
    }

    /**
     * Public, secret-free view for the frontend. Includes the Google Maps
     * browser key (the browser SDK legitimately needs it — restrict it by
     * HTTP referrer in Google Cloud).
     *
     * @return array<string, mixed>
     */
    public function publicView(): array
    {
        $all = $this->all();
        $public = [];

        foreach ($this->schema() as $key => $meta) {
            if (! $meta['secret']) {
                $public[$key] = $all[$key] ?? null;
            }
        }

        return $public;
    }

    /**
     * Admin view: non-secrets shown as-is, secrets masked to a boolean
     * "configured" flag (never the actual value).
     *
     * @return array<string, mixed>
     */
    public function adminView(): array
    {
        $all = $this->all();
        $view = [];

        foreach ($this->schema() as $key => $meta) {
            if ($meta['secret']) {
                $view[$key] = [
                    'configured' => ! empty($all[$key]),
                    'masked' => true,
                ];
            } else {
                $view[$key] = $all[$key] ?? null;
            }
        }

        return $view;
    }

    /**
     * Persist setting overrides and bust the cache. Unknown keys are ignored;
     * empty secret values are skipped so secrets are never wiped accidentally.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function update(array $values): array
    {
        $schema = $this->schema();

        foreach ($values as $key => $value) {
            if (! array_key_exists($key, $schema)) {
                continue;
            }

            // Don't overwrite a secret with an empty submission.
            if ($schema[$key]['secret'] && ($value === null || $value === '')) {
                continue;
            }

            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $this->wrap($value), 'is_secret' => $schema[$key]['secret']],
            );
        }

        $this->flush();

        return $this->adminView();
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** Values are stored JSON-wrapped so scalars round-trip cleanly. */
    private function wrap(mixed $value): array
    {
        return ['v' => $value];
    }

    private function unwrap(mixed $stored): mixed
    {
        if (is_array($stored) && array_key_exists('v', $stored)) {
            return $stored['v'];
        }

        return $stored;
    }
}
