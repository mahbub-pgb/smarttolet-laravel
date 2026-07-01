<?php

declare(strict_types=1);

namespace App\Services\Engagement;

use App\Models\ContactView;
use App\Models\Listing;
use App\Models\User;
use App\Services\Listing\ListingService;

/**
 * Web "click to show number" reveals. Backed by the same ContactView records
 * (and contact_view_count) the API uses, so contact analytics stay unified.
 */
class ContactRevealService
{
    public function __construct(private ListingService $listings) {}

    /** Whether this user has already revealed the listing owner's contact. */
    public function hasRevealed(User $user, int $listingId): bool
    {
        return ContactView::query()
            ->where('listing_id', $listingId)
            ->where('viewer_id', $user->id)
            ->exists();
    }

    /**
     * Record that the user revealed the listing's contact and return the
     * owner's mobile number. Idempotent — repeat reveals reuse the same row.
     */
    public function reveal(User $user, Listing $listing): string
    {
        $this->listings->recordContactView($listing, $user, null);

        return (string) ($listing->owner->mobile ?? '');
    }
}
