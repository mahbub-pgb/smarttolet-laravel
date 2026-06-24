<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Services\Auth\Contracts\OtpRepository;
use Illuminate\Support\Facades\DB;

/**
 * Database-backed OTP storage (no Redis dependency). Stores ONLY the SHA-256
 * hash + attempt counter in the `otps` table with a per-row expiry, plus a
 * separate cooldown row. Expired rows are treated as absent and overwritten.
 */
class DatabaseOtpRepository implements OtpRepository
{
    private const TABLE = 'otps';

    public function store(string $key, string $hash, int $ttlSeconds): void
    {
        DB::table(self::TABLE)->updateOrInsert(
            ['key' => $key],
            [
                'hash' => $hash,
                'attempts' => 0,
                'expires_at' => now()->addSeconds($ttlSeconds),
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    public function exists(string $key): bool
    {
        return $this->liveQuery($key)->exists();
    }

    public function attempts(string $key): int
    {
        return (int) ($this->liveQuery($key)->value('attempts') ?? 0);
    }

    public function incrementAttempts(string $key): int
    {
        $this->liveQuery($key)->increment('attempts');

        return $this->attempts($key);
    }

    public function hash(string $key): ?string
    {
        $value = $this->liveQuery($key)->value('hash');

        return $value !== null ? (string) $value : null;
    }

    public function forget(string $key): void
    {
        DB::table(self::TABLE)->where('key', $key)->delete();
    }

    public function cooldownActive(string $cooldownKey): bool
    {
        return $this->liveQuery($cooldownKey)->exists();
    }

    public function cooldownTtl(string $cooldownKey): int
    {
        $expiresAt = $this->liveQuery($cooldownKey)->value('expires_at');

        if ($expiresAt === null) {
            return 0;
        }

        return (int) max(0, now()->diffInSeconds($expiresAt, false));
    }

    public function startCooldown(string $cooldownKey, int $ttlSeconds): void
    {
        DB::table(self::TABLE)->updateOrInsert(
            ['key' => $cooldownKey],
            [
                'hash' => null,
                'attempts' => 0,
                'expires_at' => now()->addSeconds($ttlSeconds),
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    /** Rows that exist AND have not yet expired. */
    private function liveQuery(string $key): \Illuminate\Database\Query\Builder
    {
        return DB::table(self::TABLE)
            ->where('key', $key)
            ->where('expires_at', '>', now());
    }
}
