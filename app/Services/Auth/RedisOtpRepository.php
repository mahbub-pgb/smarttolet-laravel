<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Services\Auth\Contracts\OtpRepository;
use Illuminate\Support\Facades\Redis;

/**
 * Redis-backed OTP storage. Stores ONLY the hash + attempt counter (never the
 * plaintext code) in a hash key with a TTL, plus a separate cooldown key.
 */
class RedisOtpRepository implements OtpRepository
{
    public function store(string $key, string $hash, int $ttlSeconds): void
    {
        Redis::hmset($key, ['hash' => $hash, 'attempts' => 0]);
        Redis::expire($key, $ttlSeconds);
    }

    public function exists(string $key): bool
    {
        return (bool) Redis::exists($key);
    }

    public function attempts(string $key): int
    {
        return (int) Redis::hget($key, 'attempts');
    }

    public function incrementAttempts(string $key): int
    {
        return (int) Redis::hincrby($key, 'attempts', 1);
    }

    public function hash(string $key): ?string
    {
        $value = Redis::hget($key, 'hash');

        return $value !== null ? (string) $value : null;
    }

    public function forget(string $key): void
    {
        Redis::del($key);
    }

    public function cooldownActive(string $cooldownKey): bool
    {
        return (bool) Redis::exists($cooldownKey);
    }

    public function cooldownTtl(string $cooldownKey): int
    {
        return max(0, (int) Redis::ttl($cooldownKey));
    }

    public function startCooldown(string $cooldownKey, int $ttlSeconds): void
    {
        Redis::setex($cooldownKey, $ttlSeconds, '1');
    }
}
