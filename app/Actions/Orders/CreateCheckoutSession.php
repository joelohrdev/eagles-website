<?php

namespace App\Actions\Orders;

use App\Contracts\PaymentGateway;
use App\Enums\OrderType;
use App\Models\Order;
use App\Support\Payments\CheckoutSession;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Create a hosted checkout session for a pending order and remember its id.
 */
class CreateCheckoutSession
{
    public function __construct(
        private PaymentGateway $gateway,
        private CancelPendingOrder $cancelPendingOrder,
    ) {}

    /**
     * @throws ValidationException when the payment page can't be created; the order (and any camp hold) is cancelled first.
     */
    public function handle(Order $order, string $successUrl, string $cancelUrl): CheckoutSession
    {
        try {
            $session = $this->gateway->createCheckoutSession($order, $successUrl, $cancelUrl);
        } catch (Throwable $e) {
            report($e);

            /** Nothing can be paid, so release the spot and coupon use now instead of when the hold runs out. */
            $this->cancelPendingOrder->handle($order, 'The payment page could not be created');

            throw ValidationException::withMessages([
                $order->type === OrderType::Camp ? 'registration' : 'cart' => __('We couldn’t start the payment. Nothing was charged — please try again in a moment.'),
            ]);
        }

        $order->forceFill(['stripe_checkout_session_id' => $session->id])->save();

        return $session;
    }
}
