<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Geo / Google Maps
|--------------------------------------------------------------------------
|
| Two keys are used: a server-side key (Geocoding / Places web service) and
| a browser key the frontend SDK needs. The browser key is exposed via the
| public settings endpoint and MUST be restricted by HTTP referrer in the
| Google Cloud console.
|
*/

return [
    'google' => [
        'server_key' => env('GOOGLE_MAPS_SERVER_KEY'),
        'browser_key' => env('GOOGLE_MAPS_BROWSER_KEY'),
        'places_endpoint' => env('GOOGLE_PLACES_ENDPOINT', 'https://maps.googleapis.com/maps/api/place/nearbysearch/json'),
        'geocode_endpoint' => env('GOOGLE_GEOCODE_ENDPOINT', 'https://maps.googleapis.com/maps/api/geocode/json'),
    ],

    // Default map view (centre + zoom). Overridable at runtime in admin settings.
    'map' => [
        'default_zoom' => (int) env('GEO_MAP_DEFAULT_ZOOM', 12),
        'pinned_zoom' => (int) env('GEO_MAP_PINNED_ZOOM', 16),
        'default_lat' => (float) env('GEO_MAP_DEFAULT_LAT', 23.8103),  // Dhaka
        'default_lng' => (float) env('GEO_MAP_DEFAULT_LNG', 90.4125),
    ],

    // Default radius (metres) for nearby place searches.
    'nearby_radius' => (int) env('GEO_NEARBY_RADIUS', 1500),

    // Default radius (km) for listing $near searches when none supplied.
    'default_listing_radius_km' => (float) env('GEO_LISTING_RADIUS_KM', 5),
];
