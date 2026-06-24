<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'mobile' => $this->mobile,
            'email' => $this->email,
            'name' => $this->name,
            'photo' => $this->photo,
            'dob' => $this->dob?->toDateString(),
            'gender' => $this->gender,
            'occupation' => $this->occupation,
            'nid' => $this->nid,
            'address' => $this->address,
            'role' => $this->role->value,
            'is_phone_verified' => $this->is_phone_verified,
            'is_email_verified' => $this->is_email_verified,
            'is_landlord_verified' => $this->is_landlord_verified,
            'is_suspended' => $this->is_suspended,
            'location' => $this->latitude !== null && $this->longitude !== null
                ? ['lat' => (float) $this->latitude, 'lng' => (float) $this->longitude]
                : null,
            'area_preferences' => $this->area_preferences ?? [],
            'plan' => $this->currentPlan(),
            'permissions' => Permission::valuesForRole($this->role),
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
