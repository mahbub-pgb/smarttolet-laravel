<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Permission;
use App\Exceptions\ApiException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards a route on one or more granular permissions. Usage:
 *   ->middleware('permission:manage_users')
 *   ->middleware('permission:review_listings,approve_listings')  // any-of
 *
 * Routes are guarded on permissions, never raw roles, so capabilities can be
 * re-mapped centrally via the Role -> Permission map.
 */
class RequirePermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if ($user === null) {
            throw ApiException::unauthorized('Authentication required.', 'unauthenticated');
        }

        $required = array_map(
            fn (string $p) => Permission::tryFrom($p) ?? throw new \InvalidArgumentException("Unknown permission [{$p}]."),
            $permissions,
        );

        if (! $user->hasAnyPermission(...$required)) {
            throw ApiException::forbidden('You do not have permission to perform this action.', 'insufficient_permission');
        }

        return $next($request);
    }
}
