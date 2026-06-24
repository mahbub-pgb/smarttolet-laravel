<?php

declare(strict_types=1);

namespace App\Services\Engagement;

use App\Exceptions\ApiException;
use App\Models\Favorite;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FavoriteService
{
    /**
     * @return LengthAwarePaginator<Listing>
     */
    public function listListings(User $user, int $perPage, int $page): LengthAwarePaginator
    {
        return Listing::query()
            ->whereIn('id', $user->favorites()->select('listing_id'))
            ->with('owner:id,name,photo')
            ->latest()
            ->paginate(perPage: $perPage, page: $page);
    }

    public function add(User $user, int $listingId): Favorite
    {
        $listing = Listing::find($listingId);
        if (! $listing) {
            throw ApiException::notFound('Listing not found.', 'listing_not_found');
        }

        return Favorite::firstOrCreate([
            'user_id' => $user->id,
            'listing_id' => $listingId,
        ]);
    }

    public function remove(User $user, int $listingId): void
    {
        $user->favorites()->where('listing_id', $listingId)->delete();
    }

    /** @return array<int, int> */
    public function ids(User $user): array
    {
        return $user->favorites()->pluck('listing_id')->all();
    }
}
