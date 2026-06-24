<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Payment Gateways
|--------------------------------------------------------------------------
|
| Pluggable gateway adapters. Each gateway can run in `sandbox` mode using
| the built-in mock flow (no real credentials needed), or `live` mode once
| real credentials are supplied.
|
*/

return [
    'default' => env('PAYMENT_GATEWAY', 'bkash'),

    'currency' => 'BDT',

    'gateways' => [
        'bkash' => [
            'mode' => env('BKASH_MODE', 'sandbox'),
            'app_key' => env('BKASH_APP_KEY'),
            'app_secret' => env('BKASH_APP_SECRET'),
            'username' => env('BKASH_USERNAME'),
            'password' => env('BKASH_PASSWORD'),
            'base_url' => env('BKASH_BASE_URL', 'https://tokenized.sandbox.bka.sh/v1.2.0-beta'),
        ],
        'nagad' => [
            'mode' => env('NAGAD_MODE', 'sandbox'),
            'merchant_id' => env('NAGAD_MERCHANT_ID'),
            'merchant_key' => env('NAGAD_MERCHANT_KEY'),
            'base_url' => env('NAGAD_BASE_URL', 'https://sandbox.mynagad.com'),
        ],
        'rocket' => [
            'mode' => env('ROCKET_MODE', 'sandbox'),
            'merchant_id' => env('ROCKET_MERCHANT_ID'),
            'api_key' => env('ROCKET_API_KEY'),
            'base_url' => env('ROCKET_BASE_URL', 'https://sandbox.rocket.com.bd'),
        ],
    ],
];
