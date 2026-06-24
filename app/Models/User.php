<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Permission;
use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property string $mobile
 * @property string|null $email
 * @property Role $role
 * @property int $token_version
 * @property bool $is_phone_verified
 * @property bool $is_email_verified
 * @property bool $is_suspended
 */
class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'mobile',
        'email',
        'password',
        'name',
        'photo',
        'dob',
        'gender',
        'occupation',
        'nid',
        'address',
        'role',
        'is_phone_verified',
        'is_email_verified',
        'is_suspended',
        'is_landlord_verified',
        'latitude',
        'longitude',
        'area_preferences',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'token_version',
    ];

    protected function casts(): array
    {
        return [
            'role' => Role::class,
            'dob' => 'date',
            'is_phone_verified' => 'boolean',
            'is_email_verified' => 'boolean',
            'is_suspended' => 'boolean',
            'is_landlord_verified' => 'boolean',
            'token_version' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
            'area_preferences' => 'array',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // --- Relationships ---------------------------------------------------

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class, 'owner_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function savedSearches(): HasMany
    {
        return $this->hasMany(SavedSearch::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // --- RBAC helpers ----------------------------------------------------

    public function hasRole(Role ...$roles): bool
    {
        foreach ($roles as $role) {
            if ($this->role === $role) {
                return true;
            }
        }

        return false;
    }

    public function hasPermission(Permission $permission): bool
    {
        return in_array($permission, Permission::forRole($this->role), true);
    }

    public function hasAnyPermission(Permission ...$permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    public function isStaff(): bool
    {
        return $this->role->isStaff();
    }

    /**
     * Rank comparison used by the rank guard. A user may only act on a target
     * strictly below their own rank.
     */
    public function outranks(User $target): bool
    {
        return $this->role->rank() > $target->role->rank();
    }

    public function activeSubscription(): ?Subscription
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest('id')
            ->first();
    }

    public function currentPlan(): string
    {
        return $this->activeSubscription()?->plan ?? config('subscription.default');
    }
}
