<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Web (session) role guard for the server-rendered admin area. Unlike the API
 * RequireRole guard, this aborts with a normal HTML 403 instead of a JSON
 * envelope. Usage: ->middleware('web.role:admin,super_admin')
 */
class EnsureWebRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->guest(route('login'));
        }

        $required = array_filter(array_map(fn (string $r) => Role::tryFrom($r), $roles));

        if (! $user->hasRole(...$required)) {
            abort(403, 'You do not have access to this area.');
        }

        return $next($request);
    }
}
