<?php

declare(strict_types=1);

namespace App\Providers;

use App\Auth\JwtGuard;
use App\Models\Listing;
use App\Policies\ListingPolicy;
use App\Services\Auth\JwtService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected array $policies = [
        Listing::class => ListingPolicy::class,
    ];

    public function boot(): void
    {
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        // Register the stateless JWT guard driver.
        Auth::extend('jwt', function ($app, $name, array $config) {
            return new JwtGuard(
                provider: Auth::createUserProvider($config['provider']),
                request: $app['request'],
                jwt: $app->make(JwtService::class),
            );
        });

        // Define a Gate that maps to our permission enum so policies/controllers
        // can call Gate::authorize('manage_users') etc.
        Gate::before(function (Authenticatable $user, string $ability) {
            // Allow permission-string abilities to be resolved against the role map.
            $permission = \App\Enums\Permission::tryFrom($ability);

            if ($permission !== null && method_exists($user, 'hasPermission')) {
                return $user->hasPermission($permission) ?: null;
            }

            return null;
        });
    }
}
