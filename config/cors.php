<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Cross-Origin Resource Sharing (CORS)
|--------------------------------------------------------------------------
|
| In non-production environments any localhost / 127.0.0.1 origin is allowed
| (via the pattern below) to ease local frontend development. In production
| only the explicitly configured CLIENT_URL(s) are permitted. Credentials are
| supported so the httpOnly refresh-token cookie works cross-origin.
|
*/

$clientUrls = array_values(array_filter(array_map(
    'trim',
    explode(',', (string) env('CLIENT_URL', '')),
)));

$isProduction = env('APP_ENV') === 'production';

return [
    'paths' => ['api/*', 'broadcasting/auth'],

    'allowed_methods' => ['*'],

    'allowed_origins' => $clientUrls,

    // Allow any localhost origin (any port/scheme) outside production.
    'allowed_origins_patterns' => $isProduction
        ? []
        : ['#^https?://(localhost|127\.0\.0\.1)(:\d+)?$#'],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
