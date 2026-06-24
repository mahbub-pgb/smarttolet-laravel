<?php

declare(strict_types=1);

namespace App\Services\Auth\Contracts;

/**
 * Storage contract for OTP state. The production binding stores the SHA-256
 * hash + attempt counter in Redis under "otp:<purpose>:<id>" with a TTL, plus
 * a separate cooldown key. Abstracted so it can be swapped in tests.
 */
interface OtpRepository
{
    /** Store a fresh OTP hash with a zeroed attempt counter and a TTL. */
    public function store(string $key, string $hash, int $ttlSeconds): void;

    public function exists(string $key): bool;

    public function attempts(string $key): int;

    public function incrementAttempts(string $key): int;

    public function hash(string $key): ?string;

    public function forget(string $key): void;

    public function cooldownActive(string $cooldownKey): bool;

    /** Remaining cooldown TTL in seconds (0 if none). */
    public function cooldownTtl(string $cooldownKey): int;

    public function startCooldown(string $cooldownKey, int $ttlSeconds): void;
}
