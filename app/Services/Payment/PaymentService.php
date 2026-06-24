<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Exceptions\ApiException;
use App\Models\Payment;
use App\Models\User;
use App\Services\Notification\NotificationService;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(
        private PaymentGatewayManager $gateways,
        private SubscriptionService $subscriptions,
        private NotificationService $notifications,
    ) {}

    /**
     * Begin a subscription payment. Creates a ledger row with an idempotent
     * intent id, then asks the gateway to open a checkout session.
     *
     * @return array{payment: Payment, redirect_url: string}
     */
    public function initiate(User $user, string $plan, string $gatewayName): array
    {
        $amount = $this->subscriptions->priceForPlan($plan);

        if ($amount <= 0) {
            throw ApiException::badRequest('The selected plan is free and needs no payment.', 'plan_is_free');
        }

        $gateway = $this->gateways->gateway($gatewayName);

        $payment = Payment::create([
            'user_id' => $user->id,
            'plan' => $plan,
            'gateway' => $gateway->name(),
            'amount' => $amount,
            'currency' => config('payment.currency', 'BDT'),
            'status' => Payment::STATUS_INITIATED,
            'intent_id' => (string) Str::uuid(),
        ]);

        $result = $gateway->initiate($payment);

        $payment->forceFill([
            'status' => Payment::STATUS_PENDING,
            'gateway_ref' => $result['gateway_ref'],
            'raw' => $result['raw'],
        ])->save();

        return ['payment' => $payment, 'redirect_url' => $result['redirect_url']];
    }

    /**
     * Verify a payment and, on success, activate the plan. Idempotent: a
     * completed payment is returned as-is.
     *
     * @param  array<string, mixed>  $callback
     */
    public function verify(User $user, string $intentId, array $callback): Payment
    {
        $payment = Payment::where('intent_id', $intentId)->where('user_id', $user->id)->first();

        if (! $payment) {
            throw ApiException::notFound('Payment not found.', 'payment_not_found');
        }

        if ($payment->status === Payment::STATUS_COMPLETED) {
            return $payment; // idempotent
        }

        $gateway = $this->gateways->gateway($payment->gateway);
        $result = $gateway->verify($payment, $callback);

        if (! $result['success']) {
            $payment->forceFill([
                'status' => Payment::STATUS_FAILED,
                'raw' => $result['raw'],
            ])->save();

            throw ApiException::badRequest('Payment verification failed.', 'payment_failed');
        }

        return DB::transaction(function () use ($payment, $result, $user) {
            $subscription = $this->subscriptions->activatePlan($user, $payment->plan);

            $payment->forceFill([
                'status' => Payment::STATUS_COMPLETED,
                'gateway_ref' => $result['gateway_ref'] ?? $payment->gateway_ref,
                'subscription_id' => $subscription->id,
                'raw' => $result['raw'],
                'paid_at' => now(),
            ])->save();

            $this->notifications->notify($user->id, 'payment_completed', [
                'plan' => $payment->plan,
                'amount' => (float) $payment->amount,
            ]);

            return $payment->refresh();
        });
    }
}
