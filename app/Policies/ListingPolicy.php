<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Listing;
use App\Models\User;

/**
 * Ownership policy for listings. Owners manage their own listings; staff with
 * `manage_listings` may manage any listing.
 */
class ListingPolicy
{
    public function update(User $user, Listing $listing): bool
    {
        return $listing->isOwnedBy($user) || $user->hasPermission(Permission::ManageListings);
    }

    public function delete(User $user, Listing $listing): bool
    {
        return $listing->isOwnedBy($user) || $user->hasPermission(Permission::ManageListings);
    }

    public function moderate(User $user, Listing $listing): bool
    {
        return $user->hasAnyPermission(Permission::ReviewListings, Permission::ApproveListings);
    }
}
