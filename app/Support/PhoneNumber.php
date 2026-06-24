<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Normalises Bangladeshi mobile numbers to the canonical 11-digit local form
 * (01XXXXXXXXX) used as the user's primary identity.
 */
final class PhoneNumber
{
    public static function normalize(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        // Strip a leading country code (88 / 880).
        if (str_starts_with($digits, '880')) {
            $digits = substr($digits, 2); // -> 0XXXXXXXXXX
        } elseif (str_starts_with($digits, '88')) {
            $digits = substr($digits, 2);
        }

        // Ensure a single leading zero.
        if (! str_starts_with($digits, '0') && strlen($digits) === 10) {
            $digits = '0'.$digits;
        }

        return $digits;
    }
}
