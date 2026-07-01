<?php

declare(strict_types=1);

namespace App\Services\Engagement;

use App\Models\ContactReveal;
use App\Models\Listing;
use App\Models\User;

class ContactRevealService
{
    /** Whether this user has already revealed the listing owner's contact. */
    public function hasRevealed(User $user, int $listingId): bool
    {
        return ContactReveal::query()
            ->where('user_id', $user->id)
            ->where('listing_id', $listingId)
            ->exists();
    }

    /**
     * Record that the user revealed the listing's contact and return the
     * owner's mobile number. Idempotent — repeat reveals reuse the same row.
     */
    public function reveal(User $user, Listing $listing): string
    {
        ContactReveal::firstOrCreate([
            'user_id' => $user->id,
            'listing_id' => $listing->id,
        ]);

        return (string) ($listing->owner->mobile ?? '');
    }
}
