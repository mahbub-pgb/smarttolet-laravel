<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Role;
use App\Exceptions\ApiException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards a route on one or more roles (any-of). Prefer permission guards;
 * use this only when a capability maps cleanly to a role tier.
 *   ->middleware('role:admin,super_admin')
 */
class RequireRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            throw ApiException::unauthorized('Authentication required.', 'unauthenticated');
        }

        $required = array_map(
            fn (string $r) => Role::tryFrom($r) ?? throw new \InvalidArgumentException("Unknown role [{$r}]."),
            $roles,
        );

        if (! $user->hasRole(...$required)) {
            throw ApiException::forbidden('Your role does not allow this action.', 'insufficient_role');
        }

        return $next($request);
    }
}
