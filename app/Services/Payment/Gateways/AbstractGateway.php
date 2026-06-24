<?php

declare(strict_types=1);

namespace App\Services\Payment\Gateways;

use App\Models\Payment;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Str;

/**
 * Base adapter providing a working sandbox flow shared by all gateways. In
 * sandbox mode `initiate()` returns a mock checkout URL and `verify()` succeeds
 * when the client echoes back the issued sandbox reference. Live mode is
 * delegated to the concrete adapter (initiateLive/verifyLive).
 */
abstract class AbstractGateway implements PaymentGatewayInterface
{
    /**
     * @return array<string, mixed>
     */
    protected function config(): array
    {
        return (array) config("payment.gateways.{$this->name()}", []);
    }

    protected function isSandbox(): bool
    {
        return ($this->config()['mode'] ?? 'sandbox') === 'sandbox';
    }

    public function initiate(Payment $payment): array
    {
        if ($this->isSandbox()) {
            $ref = strtoupper($this->name()).'-SBX-'.Str::upper(Str::random(12));

            return [
                'redirect_url' => url("/api/v1/payments/sandbox/{$this->name()}?ref={$ref}&intent={$payment->intent_id}"),
                'gateway_ref' => $ref,
                'raw' => ['mode' => 'sandbox', 'ref' => $ref],
            ];
        }

        return $this->initiateLive($payment);
    }

    public function verify(Payment $payment, array $callback): array
    {
        if ($this->isSandbox()) {
            // Succeed when the client returns the issued reference (or an
            // explicit status=success), simulating a completed checkout.
            $ref = $callback['gateway_ref'] ?? $payment->gateway_ref;
            $success = ($callback['status'] ?? 'success') === 'success' && ! empty($ref);

            return [
                'success' => $success,
                'gateway_ref' => $ref,
                'raw' => ['mode' => 'sandbox', 'callback' => $callback],
            ];
        }

        return $this->verifyLive($payment, $callback);
    }

    /**
     * @return array{redirect_url: string, gateway_ref: string, raw: array<string, mixed>}
     */
    abstract protected function initiateLive(Payment $payment): array;

    /**
     * @param  array<string, mixed>  $callback
     * @return array{success: bool, gateway_ref: string|null, raw: array<string, mixed>}
     */
    abstract protected function verifyLive(Payment $payment, array $callback): array;
}
