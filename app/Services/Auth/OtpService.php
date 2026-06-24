<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Exceptions\ApiException;
use App\Services\Auth\Contracts\OtpRepository;
use App\Services\Sms\Contracts\SmsClientInterface;

/**
 * One-time password lifecycle.
 *
 * For each (purpose, identifier) we persist ONLY the SHA-256 hash + an attempt
 * counter (TTL = expiry) plus a separate cooldown key. The plaintext code is
 * delivered via SMS only and is NEVER returned by the API in any environment.
 */
class OtpService
{
    public function __construct(
        private OtpRepository $store,
        private SmsClientInterface $sms,
    ) {}

    /**
     * Generate, store and send a fresh OTP. Enforces the resend cooldown.
     */
    public function request(string $purpose, string $identifier, string $destination): void
    {
        $cooldownKey = $this->cooldownKey($purpose, $identifier);

        if ($this->store->cooldownActive($cooldownKey)) {
            $ttl = $this->store->cooldownTtl($cooldownKey);
            throw ApiException::tooManyRequests(
                "Please wait {$ttl}s before requesting another code.",
                'otp_cooldown',
                ['retry_after' => $ttl],
            );
        }

        $code = $this->generateCode();
        $key = $this->otpKey($purpose, $identifier);

        $this->store->store($key, $this->hash($code), (int) config('otp.expiry_seconds'));
        $this->store->startCooldown($cooldownKey, (int) config('otp.resend_cooldown_seconds'));

        // Deliver via SMS only.
        $minutes = (int) config('otp.expiry_seconds') / 60;
        $this->sms->send(
            $destination,
            "Your Smart To-Let verification code is {$code}. It expires in {$minutes} minutes.",
        );
    }

    /**
     * Verify a submitted code. On success the OTP is consumed (deleted). On
     * failure the attempt counter is incremented and the OTP is locked out
     * after the configured maximum.
     */
    public function verify(string $purpose, string $identifier, string $code): void
    {
        $key = $this->otpKey($purpose, $identifier);

        if (! $this->store->exists($key)) {
            throw ApiException::unprocessable('No active code. Please request a new one.', 'otp_not_found');
        }

        $attempts = $this->store->attempts($key);
        $max = (int) config('otp.max_attempts');

        if ($attempts >= $max) {
            $this->store->forget($key);
            throw ApiException::tooManyRequests('Too many attempts. Please request a new code.', 'otp_locked');
        }

        $expected = (string) $this->store->hash($key);

        if (! hash_equals($expected, $this->hash($code))) {
            $this->store->incrementAttempts($key);
            $remaining = max(0, $max - ($attempts + 1));

            throw ApiException::unprocessable(
                'The code is incorrect.',
                'otp_invalid',
                ['attempts_remaining' => $remaining],
            );
        }

        // Consume on success.
        $this->store->forget($key);
    }

    private function generateCode(): string
    {
        $length = (int) config('otp.length', 6);
        $max = (10 ** $length) - 1;

        return str_pad((string) random_int(0, $max), $length, '0', STR_PAD_LEFT);
    }

    private function hash(string $code): string
    {
        return hash('sha256', $code);
    }

    private function otpKey(string $purpose, string $identifier): string
    {
        return sprintf('%s:%s:%s', config('otp.key_prefix', 'otp'), $purpose, $identifier);
    }

    private function cooldownKey(string $purpose, string $identifier): string
    {
        return $this->otpKey($purpose, $identifier).':cooldown';
    }
}
