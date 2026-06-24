<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Granular capability flags. Routes are guarded on permissions, never on raw
 * role strings, so the role -> permission mapping can be re-balanced centrally
 * without touching route definitions.
 */
enum Permission: string
{
    case ManageUsers = 'manage_users';
    case SuspendAccounts = 'suspend_accounts';
    case DeleteAccounts = 'delete_accounts';
    case VerifyLandlords = 'verify_landlords';
    case ManageAdmins = 'manage_admins';
    case ManageModerators = 'manage_moderators';
    case ManageListings = 'manage_listings';
    case ReviewListings = 'review_listings';
    case ApproveListings = 'approve_listings';
    case ManageBlog = 'manage_blog';
    case ManageReports = 'manage_reports';
    case ResolveReports = 'resolve_reports';
    case ManageSubscriptions = 'manage_subscriptions';
    case ManagePayments = 'manage_payments';
    case ManageAdvertisements = 'manage_advertisements';
    case ManageSettings = 'manage_settings';
    case ViewAnalytics = 'view_analytics';

    /**
     * Resolve the full (inherited) permission set for a role. A role owns its
     * own permissions plus everything owned by lower-ranked roles.
     *
     * @return array<int, Permission>
     */
    public static function forRole(Role $role): array
    {
        $permissions = [];

        foreach (Role::cases() as $candidate) {
            if ($candidate->rank() <= $role->rank()) {
                $permissions = array_merge($permissions, $candidate->ownPermissions());
            }
        }

        return array_values(array_unique($permissions, SORT_REGULAR));
    }

    /**
     * @return array<int, string>
     */
    public static function valuesForRole(Role $role): array
    {
        return array_map(fn (Permission $p) => $p->value, self::forRole($role));
    }
}
