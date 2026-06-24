<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Listing
 */
class ListingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'type' => $this->type,
            'category' => $this->category,
            'rent' => $this->rent,
            'bedrooms' => $this->bedrooms,
            'bathrooms' => $this->bathrooms,
            'area_name' => $this->area_name,
            'address' => $this->address,
            'location' => $this->hasLocation()
                ? ['lat' => (float) $this->latitude, 'lng' => (float) $this->longitude]
                : null,
            'images' => $this->images ?? [],
            'amenities' => $this->amenities ?? [],
            'status' => $this->status,
            'rejection_reason' => $this->when($this->status === 'rejected', $this->rejection_reason),
            'view_count' => $this->view_count,
            'contact_view_count' => $this->contact_view_count,
            'distance_m' => $this->when(isset($this->distance_m), fn () => (float) $this->distance_m),
            'owner' => $this->whenLoaded('owner', fn () => [
                'id' => $this->owner->id,
                'name' => $this->owner->name,
                'photo' => $this->owner->photo,
            ]),
            'approved_at' => $this->approved_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
