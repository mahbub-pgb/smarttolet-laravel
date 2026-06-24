<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| OTP Settings
|--------------------------------------------------------------------------
|
| One-time passwords are stored in Redis as a SHA-256 hash alongside an
| attempt counter. The plaintext code is NEVER returned by the API and is
| delivered exclusively via SMS.
|
*/

return [
    'length' => (int) env('OTP_LENGTH', 6),
    'expiry_seconds' => (int) env('OTP_EXPIRY_SECONDS', 300),
    'max_attempts' => (int) env('OTP_MAX_ATTEMPTS', 5),
    'resend_cooldown_seconds' => (int) env('OTP_RESEND_COOLDOWN_SECONDS', 60),

    // Redis key prefixes per purpose.
    'key_prefix' => env('OTP_KEY_PREFIX', 'otp'),
];
