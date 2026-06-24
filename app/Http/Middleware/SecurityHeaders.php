<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Helmet-equivalent security headers for API responses.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '0');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-site');
        $response->headers->set('Permissions-Policy', 'geolocation=(self), microphone=(), camera=()');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // The API is JSON-only; deny embedding/scripts via a tight CSP.
        $response->headers->set('Content-Security-Policy', "default-src 'none'; frame-ancestors 'none'");

        return $response;
    }
}
