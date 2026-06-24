<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| JWT Authentication
|--------------------------------------------------------------------------
|
| Configuration for the custom JWT auth implementation that issues a
| short-lived access token (Authorization: Bearer) and a long-lived
| refresh token (httpOnly cookie). The `token_version` column on the
| users table allows invalidating all refresh tokens for a user.
|
*/

return [
    // Secret used to sign tokens. In production this MUST be set.
    'secret' => env('JWT_SECRET', env('APP_KEY')),

    'algo' => env('JWT_ALGO', 'HS256'),

    // Issuer / audience claims.
    'issuer' => env('JWT_ISSUER', env('APP_URL', 'smart-to-let')),

    // Time-to-live in seconds.
    'access_ttl' => (int) env('JWT_ACCESS_TTL', 15 * 60),        // 15 minutes
    'refresh_ttl' => (int) env('JWT_REFRESH_TTL', 30 * 24 * 60 * 60), // 30 days

    // Refresh cookie configuration.
    'refresh_cookie' => [
        'name' => env('JWT_REFRESH_COOKIE', 'stl_refresh_token'),
        'path' => '/api/v1/auth',
        'secure' => (bool) env('JWT_REFRESH_COOKIE_SECURE', env('APP_ENV') === 'production'),
        'same_site' => env('JWT_REFRESH_COOKIE_SAMESITE', 'lax'),
        'http_only' => true,
    ],
];
