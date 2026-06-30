<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Permission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Web (session) permission guard for the server-rendered admin area. Mirrors the
 * API RequirePermission guard but aborts with a normal HTML 403 instead of a
 * JSON envelope. Usage: ->middleware('web.permission:manage_blog')
 */
class EnsureWebPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->guest(route('login'));
        }

        $required = array_filter(array_map(fn (string $p) => Permission::tryFrom($p), $permissions));

        if (! $user->hasAnyPermission(...$required)) {
            abort(403, 'You do not have access to this area.');
        }

        return $next($request);
    }
}
