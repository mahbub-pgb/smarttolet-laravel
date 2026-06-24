<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Application roles, ordered by ascending power.
 *
 * The numeric rank() is used by the "rank guard": a user may never act on,
 * or assign a role to, a target whose rank is greater than or equal to their
 * own (the only exception being explicit self-service flows).
 */
enum Role: string
{
    case User = 'user';
    case Moderator = 'moderator';
    case Admin = 'admin';
    case SuperAdmin = 'super_admin';

    public function rank(): int
    {
        return match ($this) {
            self::User => 1,
            self::Moderator => 2,
            self::Admin => 3,
            self::SuperAdmin => 4,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::User => 'User',
            self::Moderator => 'Moderator',
            self::Admin => 'Admin',
            self::SuperAdmin => 'Super Admin',
        };
    }

    /** Is this role staff (moderator or above)? */
    public function isStaff(): bool
    {
        return $this->rank() >= self::Moderator->rank();
    }

    /**
     * Permissions granted directly to this role. Higher roles inherit the
     * permissions of all lower roles via Permission::forRole().
     *
     * @return array<int, Permission>
     */
    public function ownPermissions(): array
    {
        return match ($this) {
            self::User => [],
            self::Moderator => [
                Permission::ReviewListings,
                Permission::ApproveListings,
                Permission::ManageListings,
                Permission::VerifyLandlords,
                Permission::ManageReports,
                Permission::ResolveReports,
                Permission::ManageUsers,
                Permission::ManageBlog,
            ],
            self::Admin => [
                Permission::ManageModerators,
                Permission::ManageAdvertisements,
                Permission::ManageSubscriptions,
                Permission::ManageSettings,
                Permission::ViewAnalytics,
                Permission::SuspendAccounts,
            ],
            self::SuperAdmin => [
                Permission::ManageAdmins,
                Permission::ManagePayments,
                Permission::DeleteAccounts,
            ],
        };
    }
}
