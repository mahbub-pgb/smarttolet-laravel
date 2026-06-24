<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionResource;
use App\Services\Payment\PaymentService;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $payments,
        private SubscriptionService $subscriptions,
    ) {}

    /** GET /payments/plans — available plans + pricing. */
    public function plans(): JsonResponse
    {
        return $this->ok(config('subscription.plans'), 'OK');
    }

    /** GET /payments/subscription — the caller's active subscription. */
    public function subscription(Request $request): JsonResponse
    {
        $subscription = $this->subscriptions->current($request->user());

        return $this->ok(new SubscriptionResource($subscription), 'OK');
    }

    /** POST /payments/initiate */
    public function initiate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'plan' => ['required', 'string', Rule::in(array_keys((array) config('subscription.plans')))],
            'gateway' => ['required', Rule::in(array_keys((array) config('payment.gateways')))],
        ]);

        $result = $this->payments->initiate($request->user(), $data['plan'], $data['gateway']);

        return $this->created([
            'payment_id' => $result['payment']->id,
            'intent_id' => $result['payment']->intent_id,
            'gateway' => $result['payment']->gateway,
            'amount' => (float) $result['payment']->amount,
            'redirect_url' => $result['redirect_url'],
        ], 'Payment initiated.');
    }

    /** POST /payments/verify */
    public function verify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'intent_id' => ['required', 'string'],
            'gateway_ref' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'nullable', 'string'],
        ]);

        $payment = $this->payments->verify($request->user(), $data['intent_id'], [
            'gateway_ref' => $data['gateway_ref'] ?? null,
            'status' => $data['status'] ?? 'success',
        ]);

        return $this->ok([
            'payment_id' => $payment->id,
            'status' => $payment->status,
            'subscription' => new SubscriptionResource($this->subscriptions->current($request->user())),
        ], 'Payment verified.');
    }
}
