<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Services\Auth\Contracts\OtpRepository;

/**
 * In-memory OTP store for tests — mirrors RedisOtpRepository semantics without
 * needing a live Redis server. Exposes the stored hash so tests can derive the
 * code deterministically (the production API never reveals it).
 */
class ArrayOtpRepository implements OtpRepository
{
    /** @var array<string, array{hash: string, attempts: int, expires_at: float}> */
    private array $otps = [];

    /** @var array<string, float> */
    private array $cooldowns = [];

    public function store(string $key, string $hash, int $ttlSeconds): void
    {
        $this->otps[$key] = ['hash' => $hash, 'attempts' => 0, 'expires_at' => microtime(true) + $ttlSeconds];
    }

    public function exists(string $key): bool
    {
        $entry = $this->otps[$key] ?? null;
        if ($entry === null) {
            return false;
        }
        if ($entry['expires_at'] < microtime(true)) {
            unset($this->otps[$key]);

            return false;
        }

        return true;
    }

    public function attempts(string $key): int
    {
        return $this->otps[$key]['attempts'] ?? 0;
    }

    public function incrementAttempts(string $key): int
    {
        if (! isset($this->otps[$key])) {
            return 0;
        }

        return ++$this->otps[$key]['attempts'];
    }

    public function hash(string $key): ?string
    {
        return $this->otps[$key]['hash'] ?? null;
    }

    public function forget(string $key): void
    {
        unset($this->otps[$key]);
    }

    public function cooldownActive(string $cooldownKey): bool
    {
        $until = $this->cooldowns[$cooldownKey] ?? 0;

        return $until > microtime(true);
    }

    public function cooldownTtl(string $cooldownKey): int
    {
        $until = $this->cooldowns[$cooldownKey] ?? 0;

        return (int) max(0, ceil($until - microtime(true)));
    }

    public function startCooldown(string $cooldownKey, int $ttlSeconds): void
    {
        $this->cooldowns[$cooldownKey] = microtime(true) + $ttlSeconds;
    }
}
