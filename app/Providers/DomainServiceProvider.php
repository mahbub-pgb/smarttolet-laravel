<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Contracts\ListingRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\ListingRepository;
use App\Repositories\UserRepository;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\PaymentGatewayManager;
use App\Services\Sms\Contracts\SmsClientInterface;
use App\Services\Sms\SmsManager;
use Illuminate\Support\ServiceProvider;

/**
 * Binds repository + integration interfaces to their implementations so the
 * service layer depends only on contracts (swappable / mockable).
 */
class DomainServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        UserRepositoryInterface::class => UserRepository::class,
        ListingRepositoryInterface::class => ListingRepository::class,
        \App\Services\Auth\Contracts\OtpRepository::class => \App\Services\Auth\RedisOtpRepository::class,
    ];

    public function register(): void
    {
        // Settings resolve DB -> env and are cached; share one instance.
        $this->app->singleton(\App\Services\Settings\SettingsService::class);

        // SMS: resolve the active driver via the manager.
        $this->app->singleton(SmsManager::class);
        $this->app->bind(SmsClientInterface::class, fn ($app) => $app->make(SmsManager::class)->driver());

        // Payment: resolve the active gateway via the manager.
        $this->app->singleton(PaymentGatewayManager::class);
        $this->app->bind(PaymentGatewayInterface::class, fn ($app) => $app->make(PaymentGatewayManager::class)->gateway());
    }
}
