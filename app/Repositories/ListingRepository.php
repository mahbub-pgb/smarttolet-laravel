<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Listing;
use App\Repositories\Contracts\ListingRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * @extends BaseRepository<Listing>
 */
class ListingRepository extends BaseRepository implements ListingRepositoryInterface
{
    protected function model(): Model
    {
        return new Listing;
    }

    public function findBySlug(string $slug): ?Listing
    {
        return $this->query()->where('slug', $slug)->first();
    }

    public function findByIdOrSlug(string $idOrSlug): ?Listing
    {
        $query = $this->query();

        if (ctype_digit($idOrSlug)) {
            return $query->where('id', (int) $idOrSlug)->orWhere('slug', $idOrSlug)->first();
        }

        return $query->where('slug', $idOrSlug)->first();
    }

    public function countActiveForOwner(int $ownerId): int
    {
        return $this->query()
            ->where('owner_id', $ownerId)
            ->whereIn('status', [Listing::STATUS_PENDING, Listing::STATUS_APPROVED, Listing::STATUS_DRAFT])
            ->count();
    }

    public function search(array $filters, int $perPage, int $page): LengthAwarePaginator
    {
        $query = $this->query();

        // Visibility: by default only publicly approved & unexpired listings.
        if (($filters['_scope'] ?? 'public') === 'public') {
            $query->publiclyVisible();
        } elseif (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $this->applyKeyword($query, $filters['q'] ?? null);
        $this->applyFilters($query, $filters);
        $hasGeo = $this->applyGeo($query, $filters);

        $this->applySorting($query, $filters['sort'] ?? null, $hasGeo);

        return $query->with('owner:id,name,mobile,photo')->paginate(perPage: $perPage, page: $page);
    }

    /**
     * @param  Builder<Listing>  $query
     */
    private function applyKeyword(Builder $query, ?string $keyword): void
    {
        $keyword = is_string($keyword) ? trim($keyword) : '';
        if ($keyword === '') {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            // FULLTEXT (natural language) across title/description/area_name.
            $query->whereRaw(
                'MATCH(title, description, area_name) AGAINST (? IN NATURAL LANGUAGE MODE)',
                [$keyword],
            );
        } else {
            $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $keyword).'%';
            $query->where(function ($q) use ($like) {
                $q->where('title', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhere('area_name', 'like', $like);
            });
        }
    }

    /**
     * @param  Builder<Listing>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }
        if (! empty($filters['occupancy'])) {
            // Match a single occupancy rule (e.g. family_only) inside the
            // JSON array. whereJsonContains works on MySQL and SQLite (JSON1).
            $query->whereJsonContains('occupancy_rules', $filters['occupancy']);
        }
        if (! empty($filters['area'])) {
            $query->where('area_name', 'like', '%'.$filters['area'].'%');
        }
        if (isset($filters['min_rent'])) {
            $query->where('rent', '>=', (int) $filters['min_rent']);
        }
        if (isset($filters['max_rent'])) {
            $query->where('rent', '<=', (int) $filters['max_rent']);
        }
        if (isset($filters['bedrooms'])) {
            $query->where('bedrooms', '>=', (int) $filters['bedrooms']);
        }
        if (isset($filters['bathrooms'])) {
            $query->where('bathrooms', '>=', (int) $filters['bathrooms']);
        }
        if (! empty($filters['owner_id'])) {
            $query->where('owner_id', (int) $filters['owner_id']);
        }
    }

    /**
     * Geo radius search. Uses a bounding-box prefilter (driver-agnostic, index
     * friendly) and orders by precise distance — ST_Distance_Sphere on MySQL,
     * a squared-euclidean approximation elsewhere.
     *
     * @param  Builder<Listing>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyGeo(Builder $query, array $filters): bool
    {
        if (! isset($filters['lat'], $filters['lng'])) {
            return false;
        }

        $lat = (float) $filters['lat'];
        $lng = (float) $filters['lng'];
        $radiusKm = (float) ($filters['radius'] ?? config('geo.default_listing_radius_km'));

        // Bounding box.
        $latDelta = $radiusKm / 111.0;
        $cos = max(0.000001, cos(deg2rad($lat)));
        $lngDelta = $radiusKm / (111.0 * $cos);

        $query->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereBetween('latitude', [$lat - $latDelta, $lat + $latDelta])
            ->whereBetween('longitude', [$lng - $lngDelta, $lng + $lngDelta]);

        if (DB::getDriverName() === 'mysql') {
            $query->select('listings.*')
                ->selectRaw(
                    'ST_Distance_Sphere(POINT(longitude, latitude), POINT(?, ?)) AS distance_m',
                    [$lng, $lat],
                )
                ->having('distance_m', '<=', $radiusKm * 1000);
        } else {
            // Approximate squared distance for ordering (no trig dependency).
            $query->select('listings.*')
                ->selectRaw(
                    '((latitude - ?) * (latitude - ?) + (longitude - ?) * (longitude - ?)) AS distance_m',
                    [$lat, $lat, $lng, $lng],
                );
        }

        return true;
    }

    /**
     * @param  Builder<Listing>  $query
     */
    private function applySorting(Builder $query, ?string $sort, bool $hasGeo): void
    {
        switch ($sort) {
            case 'price_asc':
                $query->orderBy('rent');
                break;
            case 'price_desc':
                $query->orderByDesc('rent');
                break;
            case 'oldest':
                $query->orderBy('created_at');
                break;
            case 'popular':
                $query->orderByDesc('view_count');
                break;
            case 'nearest':
            default:
                if ($hasGeo) {
                    $query->orderBy('distance_m');
                } else {
                    $query->latest();
                }
        }
    }
}
