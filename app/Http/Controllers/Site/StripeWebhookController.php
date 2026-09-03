<?php

namespace App\Http\Controllers\Site;

use App\Actions\Orders\CancelPendingOrder;
use App\Actions\Orders\MarkOrderPaid;
use App\Contracts\PaymentGateway;
use App\Exceptions\InvalidWebhookSignature;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StripeWebhookController extends Controller
{
    public function __construct(
        private PaymentGateway $gateway,
        private MarkOrderPaid $markOrderPaid,
        private CancelPendingOrder $cancelPendingOrder,
    ) {}

    /**
     * Handle Stripe webhook events (signature-verified, CSRF-exempt).
     */
    public function __invoke(Request $request): Response
    {
        try {
            $event = $this->gateway->parseWebhook($request->getContent(), $request->header('Stripe-Signature'));
        } catch (InvalidWebhookSignature) {
            return response('Invalid signature', 400);
        }

        if ($event->checkoutSessionId === null) {
            return response('Ignored', 200);
        }

        $order = Order::query()->where('stripe_checkout_session_id', $event->checkoutSessionId)->first();

        if ($order === null) {
            return response('Unknown order', 200);
        }

        if ($event->isCheckoutCompleted()) {
            $this->markOrderPaid->handle($order, $event->paymentIntentId);
        } elseif ($event->isCheckoutExpired()) {
            $this->cancelPendingOrder->handle($order);
        }

        return response('OK', 200);
    }
}
