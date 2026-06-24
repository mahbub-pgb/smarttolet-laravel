<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\Role;
use App\Exceptions\ApiException;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Staff/user administration with the rank guard: an actor may only act on a
 * target strictly below their own rank, and may never assign a role greater
 * than or equal to their own.
 */
class UserManagementService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<User>
     */
    public function paginate(array $filters, int $perPage, int $page): LengthAwarePaginator
    {
        return User::query()
            ->when(! empty($filters['role']), fn ($q) => $q->where('role', $filters['role']))
            ->when(isset($filters['suspended']), fn ($q) => $q->where('is_suspended', (bool) $filters['suspended']))
            ->when(! empty($filters['q']), function ($q) use ($filters) {
                $term = '%'.$filters['q'].'%';
                $q->where(fn ($w) => $w->where('name', 'like', $term)
                    ->orWhere('mobile', 'like', $term)
                    ->orWhere('email', 'like', $term));
            })
            ->latest()
            ->paginate(perPage: $perPage, page: $page);
    }

    public function assignRole(User $actor, User $target, Role $newRole): User
    {
        $this->assertCanActOn($actor, $target);

        // Cannot grant a role greater than or equal to your own.
        if ($newRole->rank() >= $actor->role->rank()) {
            throw ApiException::forbidden('You cannot assign a role equal to or above your own.', 'rank_violation');
        }

        $target->role = $newRole;
        // Bump token_version so the target's stale tokens reflecting the old
        // role are invalidated.
        $target->increment('token_version');
        $target->save();

        return $target->refresh();
    }

    public function setSuspended(User $actor, User $target, bool $suspended): User
    {
        $this->assertCanActOn($actor, $target);

        $target->is_suspended = $suspended;
        if ($suspended) {
            $target->increment('token_version'); // force logout
        }
        $target->save();

        return $target->refresh();
    }

    public function setLandlordVerified(User $target, bool $verified): User
    {
        $target->forceFill(['is_landlord_verified' => $verified])->save();

        return $target->refresh();
    }

    public function delete(User $actor, User $target): void
    {
        $this->assertCanActOn($actor, $target);
        $target->delete();
    }

    private function assertCanActOn(User $actor, User $target): void
    {
        if ($actor->id === $target->id) {
            throw ApiException::forbidden('You cannot perform this action on your own account.', 'self_action');
        }

        if (! $actor->outranks($target)) {
            throw ApiException::forbidden('You cannot act on a user of equal or higher rank.', 'rank_violation');
        }
    }
}
