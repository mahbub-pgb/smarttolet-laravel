<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| SMS Drivers
|--------------------------------------------------------------------------
|
| Pluggable SMS providers. The `mock` driver logs messages instead of
| sending them (useful for local/dev/test). The `bulksmsbd` driver talks
| to the BulkSMSBD HTTP API. The active driver and its credentials can be
| overridden at runtime by the SettingsService.
|
*/

return [
    'default' => env('SMS_PROVIDER', 'mock'),

    'sender_id' => env('SMS_SENDER_ID', 'SmartToLet'),

    'drivers' => [
        'mock' => [
            // Logged to the default log channel.
        ],

        'bulksmsbd' => [
            'endpoint' => env('SMS_BULKSMSBD_ENDPOINT', 'https://bulksmsbd.net/api/smsapi'),
            'api_key' => env('SMS_API_KEY'),
        ],
    ],
];
