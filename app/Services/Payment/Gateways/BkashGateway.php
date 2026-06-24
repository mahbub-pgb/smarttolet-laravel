<?php

declare(strict_types=1);

namespace App\Services\Payment\Gateways;

use App\Exceptions\ApiException;
use App\Models\Payment;

/**
 * bKash Tokenized Checkout adapter. The sandbox flow is handled by the base
 * class; live integration (grant token → create → execute) is stubbed with the
 * correct shape for credentials to be dropped in.
 */
class BkashGateway extends AbstractGateway
{
    public function name(): string
    {
        return 'bkash';
    }

    protected function initiateLive(Payment $payment): array
    {
        // TODO: POST {base_url}/tokenized/checkout/create with the grant token.
        throw ApiException::badRequest('bKash live mode is not configured.', 'gateway_unconfigured');
    }

    protected function verifyLive(Payment $payment, array $callback): array
    {
        // TODO: POST {base_url}/tokenized/checkout/execute with paymentID.
        throw ApiException::badRequest('bKash live mode is not configured.', 'gateway_unconfigured');
    }
}
