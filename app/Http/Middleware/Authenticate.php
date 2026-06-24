<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures the request carries a valid JWT access token. Resolves the user via
 * the stateless `api` guard and sets it as the default authenticated user so
 * `$request->user()`, `auth()->user()`, policies and Gates all work.
 */
class Authenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('api')->user();

        if ($user === null) {
            throw ApiException::unauthorized('Authentication required.', 'unauthenticated');
        }

        Auth::setUser($user);

        return $next($request);
    }
}
