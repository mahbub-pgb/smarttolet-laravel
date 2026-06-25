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

    /** Selectable property types. */
    public const TYPES = ['apartment', 'room', 'sublet', 'office', 'shop', 'house', 'garage', 'hostel'];

    /** Amenity key => human label (checkboxes on the listing form). */
    public const AMENITIES = [
        'parking' => 'Parking',
        'lift' => 'Lift/Elevator',
        'generator' => 'Generator backup',
        'wifi' => 'WiFi',
        'gas' => 'Gas connection',
        'ac' => 'Air conditioning',
        'security' => 'Security guard',
        'cctv' => 'CCTV',
        'gym' => 'Gym',
        'swimming_pool' => 'Swimming pool',
        'pet_friendly' => 'Pet friendly',
    ];

    /** Occupancy & rule key => human label (checkboxes on the listing form). */
    public const OCCUPANCY_RULES = [
        'family_only' => 'Family only',
        'bachelor_allowed' => 'Bachelor allowed',
        'female_only' => 'Female only',
        'male_only' => 'Male only',
        'smoking_allowed' => 'Smoking allowed',
        'pets_allowed' => 'Pets allowed',
    ];

    protected $fillable = [
        'owner_id',
        'title',
        'slug',
        'description',
        'type',
        'category',
        'rent',
        'advance_amount',
        'available_from',
        'bedrooms',
        'bathrooms',
        'area_sqft',
        'balconies',
        'floor_number',
        'building_floors',
        'area_name',
        'address',
        'latitude',
        'longitude',
        'images',
        'video_tour_url',
        'amenities',
        'occupancy_rules',
        'status',
        'rejection_reason',
        'approved_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'rent' => 'integer',
            'advance_amount' => 'integer',
            'available_from' => 'date',
            'bedrooms' => 'integer',
            'bathrooms' => 'integer',
            'area_sqft' => 'integer',
            'balconies' => 'integer',
            'floor_number' => 'integer',
            'building_floors' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
            'images' => 'array',
            'amenities' => 'array',
            'occupancy_rules' => 'array',
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

    /** Whether this listing is currently visible to the public. */
    public function isPubliclyVisible(): bool
    {
        return $this->status === self::STATUS_APPROVED
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /**
     * Normalised YouTube embed URL for the video tour, or null when the stored
     * URL is empty or not a recognisable YouTube link.
     */
    public function youtubeEmbedUrl(): ?string
    {
        if (empty($this->video_tour_url)) {
            return null;
        }

        if (preg_match('#(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|shorts/|v/))([\w-]{11})#i', $this->video_tour_url, $m)) {
            return 'https://www.youtube.com/embed/'.$m[1];
        }

        return null;
    }
}
