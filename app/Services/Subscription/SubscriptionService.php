<?php

declare(strict_types=1);

namespace App\Services\Subscription;

use App\Exceptions\ApiException;
use App\Models\Subscription;
use App\Models\User;

class SubscriptionService
{
    public function current(User $user): Subscription
    {
        return $user->activeSubscription() ?? $this->ensureFree($user);
    }

    /**
     * Activate (or upgrade to) a paid plan. Previous active subscriptions are
     * expired so only one is active at a time.
     */
    public function activatePlan(User $user, string $plan): Subscription
    {
        $config = config("subscription.plans.{$plan}");

        if ($config === null) {
            throw ApiException::badRequest("Unknown plan [{$plan}].", 'unknown_plan');
        }

        $user->subscriptions()
            ->where('status', 'active')
            ->update(['status' => 'expired']);

        $durationDays = $config['duration_days'] ?? null;

        return $user->subscriptions()->create([
            'plan' => $plan,
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => $durationDays ? now()->addDays((int) $durationDays) : null,
        ]);
    }

    public function ensureFree(User $user): Subscription
    {
        return $user->subscriptions()->firstOrCreate(
            ['plan' => config('subscription.default', 'free'), 'status' => 'active'],
            ['started_at' => now(), 'expires_at' => null],
        );
    }

    public function priceForPlan(string $plan): int
    {
        return (int) (config("subscription.plans.{$plan}.price") ?? 0);
    }
}
