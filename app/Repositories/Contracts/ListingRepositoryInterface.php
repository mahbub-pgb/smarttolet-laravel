<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Listing;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * @extends RepositoryInterface<Listing>
 */
interface ListingRepositoryInterface extends RepositoryInterface
{
    public function findBySlug(string $slug): ?Listing;

    /** Resolve by numeric id OR slug. */
    public function findByIdOrSlug(string $idOrSlug): ?Listing;

    /**
     * Public search/browse with keyword (fulltext), filters, geo radius,
     * sorting and pagination.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<Listing>
     */
    public function search(array $filters, int $perPage, int $page): LengthAwarePaginator;

    public function countActiveForOwner(int $ownerId): int;
}
