<?php

declare(strict_types=1);

namespace App\Services\Payment\Gateways;

use App\Exceptions\ApiException;
use App\Models\Payment;

class RocketGateway extends AbstractGateway
{
    public function name(): string
    {
        return 'rocket';
    }

    protected function initiateLive(Payment $payment): array
    {
        throw ApiException::badRequest('Rocket live mode is not configured.', 'gateway_unconfigured');
    }

    protected function verifyLive(Payment $payment, array $callback): array
    {
        throw ApiException::badRequest('Rocket live mode is not configured.', 'gateway_unconfigured');
    }
}
