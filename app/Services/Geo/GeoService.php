<?php

declare(strict_types=1);

namespace App\Services\Geo;

use App\Services\Settings\SettingsService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Server-side Google Maps integration: reverse/forward geocoding and nearby
 * places. Uses the SERVER key (never the browser key). Results are cached
 * briefly to limit quota usage.
 */
class GeoService
{
    public function __construct(private SettingsService $settings) {}

    /**
     * Nearby points of interest (schools, hospitals, etc.) around a coordinate.
     *
     * @return array<int, array<string, mixed>>
     */
    public function nearbyPlaces(float $lat, float $lng, ?string $type = null, ?int $radius = null): array
    {
        $key = $this->serverKey();
        if ($key === null) {
            return [];
        }

        $radius ??= (int) config('geo.nearby_radius');

        try {
            $response = Http::timeout(15)->get(config('geo.google.places_endpoint'), array_filter([
                'location' => "{$lat},{$lng}",
                'radius' => $radius,
                'type' => $type,
                'key' => $key,
            ]));

            if (! $response->successful()) {
                return [];
            }

            return collect($response->json('results', []))
                ->map(fn (array $place) => [
                    'name' => $place['name'] ?? null,
                    'types' => $place['types'] ?? [],
                    'vicinity' => $place['vicinity'] ?? null,
                    'rating' => $place['rating'] ?? null,
                    'location' => [
                        'lat' => $place['geometry']['location']['lat'] ?? null,
                        'lng' => $place['geometry']['location']['lng'] ?? null,
                    ],
                ])
                ->all();
        } catch (Throwable $e) {
            Log::warning('[geo] nearbyPlaces failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Reverse geocode a coordinate into a formatted address + area name.
     *
     * @return array{formatted_address: ?string, area_name: ?string}|null
     */
    public function reverseGeocode(float $lat, float $lng): ?array
    {
        $key = $this->serverKey();
        if ($key === null) {
            return null;
        }

        try {
            $response = Http::timeout(15)->get(config('geo.google.geocode_endpoint'), [
                'latlng' => "{$lat},{$lng}",
                'key' => $key,
            ]);

            $result = $response->json('results.0');
            if (! $result) {
                return null;
            }

            $area = collect($result['address_components'] ?? [])
                ->first(fn ($c) => in_array('sublocality', $c['types'] ?? [], true)
                    || in_array('locality', $c['types'] ?? [], true));

            return [
                'formatted_address' => $result['formatted_address'] ?? null,
                'area_name' => $area['long_name'] ?? null,
            ];
        } catch (Throwable $e) {
            Log::warning('[geo] reverseGeocode failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    private function serverKey(): ?string
    {
        $key = $this->settings->get('google_maps_server_key', config('geo.google.server_key'));

        return ! empty($key) ? (string) $key : null;
    }
}
