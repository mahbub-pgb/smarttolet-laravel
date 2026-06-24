<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $owner_id
 * @property string $title
 * @property string $slug
 * @property string $status
 * @property float|null $latitude
 * @property float|null $longitude
 */
class Listing extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_RENTED = 'rented';

    protected $fillable = [
        'owner_id',
        'title',
        'slug',
        'description',
        'type',
        'category',
        'rent',
        'bedrooms',
        'bathrooms',
        'area_name',
        'address',
        'latitude',
        'longitude',
        'images',
        'amenities',
        'status',
        'rejection_reason',
        'approved_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'rent' => 'integer',
            'bedrooms' => 'integer',
            'bathrooms' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
            'images' => 'array',
            'amenities' => 'array',
            'view_count' => 'integer',
            'contact_view_count' => 'integer',
            'approved_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Generate a unique slug from the title on create (and on title change
        // if the slug was not explicitly provided).
        static::saving(function (Listing $listing) {
            if (empty($listing->slug) && ! empty($listing->title)) {
                $listing->slug = static::uniqueSlug($listing->title, $listing->id);
            }
        });
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        if ($base === '') {
            $base = 'listing';
        }

        $slug = $base;
        $i = 1;

        while (static::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->withTrashed()
            ->exists()
        ) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // --- Relationships ---------------------------------------------------

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(ListingVisit::class);
    }

    public function contactViews(): HasMany
    {
        return $this->hasMany(ContactView::class);
    }

    // --- Scopes ----------------------------------------------------------

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    public function scopeOwnedBy(Builder $query, int $userId): Builder
    {
        return $query->where('owner_id', $userId);
    }

    // --- Helpers ---------------------------------------------------------

    public function isOwnedBy(User $user): bool
    {
        return (int) $this->owner_id === (int) $user->id;
    }

    public function hasLocation(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }
}
