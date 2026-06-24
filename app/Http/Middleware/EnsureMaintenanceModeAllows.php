<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use App\Services\Settings\SettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * When maintenance mode is enabled, blocks normal users but lets staff
 * (moderator and above) through. MUST run after authentication so it can see
 * the resolved user's role.
 */
class EnsureMaintenanceModeAllows
{
    public function __construct(private SettingsService $settings) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->settings->isMaintenanceMode()) {
            return $next($request);
        }

        $user = $request->user();

        if ($user !== null && $user->isStaff()) {
            return $next($request);
        }

        throw new ApiException(
            'The platform is under maintenance. Please try again later.',
            503,
            'maintenance_mode',
        );
    }
}
