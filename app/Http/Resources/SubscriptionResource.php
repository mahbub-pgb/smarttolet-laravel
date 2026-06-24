<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Subscription
 */
class SubscriptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $config = $this->planConfig();

        return [
            'id' => $this->id,
            'plan' => $this->plan,
            'label' => $config['label'] ?? $this->plan,
            'status' => $this->status,
            'listing_limit' => $config['listing_limit'] ?? null,
            'featured' => $config['featured'] ?? false,
            'started_at' => $this->started_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'is_active' => $this->isActive(),
        ];
    }
}
