<?php

declare(strict_types=1);

namespace App\Services\Payment\Contracts;

use App\Models\Payment;

/**
 * Pluggable payment gateway adapter. Each gateway supports a `sandbox` mode
 * (the built-in mock flow, no real credentials needed) and a `live` mode.
 */
interface PaymentGatewayInterface
{
    public function name(): string;

    /**
     * Begin a payment. Returns a redirect/checkout URL the client opens, plus a
     * gateway reference to correlate the later verification.
     *
     * @return array{redirect_url: string, gateway_ref: string, raw: array<string, mixed>}
     */
    public function initiate(Payment $payment): array;

    /**
     * Verify/execute a payment after the customer completes checkout.
     *
     * @param  array<string, mixed>  $callback  Data returned by the gateway/client.
     * @return array{success: bool, gateway_ref: string|null, raw: array<string, mixed>}
     */
    public function verify(Payment $payment, array $callback): array;
}
