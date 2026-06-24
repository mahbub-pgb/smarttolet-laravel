<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\Gateways\BkashGateway;
use App\Services\Payment\Gateways\NagadGateway;
use App\Services\Payment\Gateways\RocketGateway;
use InvalidArgumentException;

class PaymentGatewayManager
{
    public function gateway(?string $name = null): PaymentGatewayInterface
    {
        $name ??= (string) config('payment.default', 'bkash');

        return match ($name) {
            'bkash' => new BkashGateway(),
            'nagad' => new NagadGateway(),
            'rocket' => new RocketGateway(),
            default => throw new InvalidArgumentException("Unsupported payment gateway [{$name}]."),
        };
    }
}
