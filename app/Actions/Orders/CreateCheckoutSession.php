<?php

namespace App\Actions\Orders;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use App\Support\Payments\CheckoutSession;

/**
 * Create a hosted checkout session for a pending order and remember its id.
 */
class CreateCheckoutSession
{
    public function __construct(private PaymentGateway $gateway) {}

    public function handle(Order $order, string $successUrl, string $cancelUrl): CheckoutSession
    {
        $session = $this->gateway->createCheckoutSession($order, $successUrl, $cancelUrl);

        $order->forceFill(['stripe_checkout_session_id' => $session->id])->save();

        return $session;
    }
}
