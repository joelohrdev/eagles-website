<?php

namespace App\Http\Controllers\Site;

use App\Actions\Orders\CancelPendingOrder;
use App\Actions\Orders\MarkOrderPaid;
use App\Actions\Orders\RefundOrder;
use App\Contracts\PaymentGateway;
use App\Exceptions\InvalidWebhookSignature;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\ActivityLogger;
use App\Services\StripeSettings;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StripeWebhookController extends Controller
{
    public function __construct(
        private PaymentGateway $gateway,
        private MarkOrderPaid $markOrderPaid,
        private CancelPendingOrder $cancelPendingOrder,
        private RefundOrder $refundOrder,
        private StripeSettings $stripe,
        private ActivityLogger $activity,
    ) {}

    /**
     * Handle Stripe webhook events (signature-verified, CSRF-exempt).
     */
    public function __invoke(Request $request): Response
    {
        try {
            $event = $this->gateway->parseWebhook($request->getContent(), $request->header('Stripe-Signature'));
        } catch (InvalidWebhookSignature) {
            /** Only a request that carries Stripe's header can mean the signing secret is wrong; anything else is noise. */
            if ($request->hasHeader('Stripe-Signature')) {
                $this->stripe->recordWebhook(verified: false);
            }

            return response('Invalid signature', 400);
        }

        $this->stripe->recordWebhook(verified: true);
        $this->activity->actingAs(ActivityLogger::STRIPE);

        if (! $event->identifiesOrder()) {
            return response('Ignored', 200);
        }

        $order = Order::query()
            ->when($event->checkoutSessionId !== null,
                fn ($q) => $q->where('stripe_checkout_session_id', $event->checkoutSessionId),
                fn ($q) => $q->where('stripe_payment_intent_id', $event->paymentIntentId),
            )
            ->first();

        if ($order === null) {
            return response('Unknown order', 200);
        }

        if ($event->isCheckoutCompleted()) {
            $this->markOrderPaid->handle($order, $event->paymentIntentId);
        } elseif ($event->isCheckoutExpired()) {
            $this->cancelPendingOrder->handle($order, 'Stripe payment page expired');
        } elseif ($event->isFullyRefunded()) {
            $this->refundOrder->handle($order);
        } elseif ($event->isPartiallyRefunded() && $order->isPaid() && $event->amountRefunded > $order->refunded_amount) {
            /** Stripe reports the running total refunded, so a refund made in the dashboard is recorded too. */
            $order->forceFill(['refunded_amount' => min($event->amountRefunded, $order->total)])->save();
            $this->activity->log('order.partially_refunded', 'Order '.$order->number.' partly refunded in Stripe ($'.number_format($order->refunded_amount / 100, 2).' back so far)', $order, [
                'refunded_amount' => $order->refunded_amount,
            ]);
        }

        return response('OK', 200);
    }
}
