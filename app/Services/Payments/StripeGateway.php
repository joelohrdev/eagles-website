<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Exceptions\InvalidWebhookSignature;
use App\Models\Order;
use App\Models\OrderItem;
use App\Support\Payments\CheckoutSession;
use App\Support\Payments\WebhookEvent;
use Illuminate\Support\Str;
use Stripe\Charge;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeGateway implements PaymentGateway
{
    public function __construct(private StripeClient $stripe, private string $webhookSecret) {}

    public function isAvailable(): bool
    {
        return true;
    }

    public function createCheckoutSession(Order $order, string $successUrl, string $cancelUrl): CheckoutSession
    {
        $order->loadMissing(['items', 'campRegistration']);

        /** A camp spot is only held until expires_at, so the payment page must close then too. */
        $expiresAt = $order->campRegistration?->expires_at;

        $session = $this->stripe->checkout->sessions->create([
            ...($expiresAt ? ['expires_at' => $expiresAt->getTimestamp()] : []),
            ...($order->discount > 0 ? ['discounts' => [['coupon' => $this->oneTimeCoupon($order)]]] : []),
            'mode' => 'payment',
            'customer_email' => $order->email,
            'client_reference_id' => $order->number,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => [
                'order_id' => (string) $order->id,
                'order_number' => $order->number,
                'order_type' => $order->type->value,
            ],
            'line_items' => $order->items->map(fn (OrderItem $item) => [
                'quantity' => $item->quantity,
                'price_data' => [
                    'currency' => 'usd',
                    'unit_amount' => $item->unit_price,
                    'product_data' => [
                        'name' => $item->description,
                        'description' => collect([$item->size, $item->color])->filter()->implode(' / ') ?: null,
                    ],
                ],
            ])->map(fn (array $line) => tap($line, function (array &$l) {
                if ($l['price_data']['product_data']['description'] === null) {
                    unset($l['price_data']['product_data']['description']);
                }
            }))->values()->all(),
        ]);

        return new CheckoutSession(id: $session->id, url: $session->url);
    }

    /**
     * Stripe line items can't be negative, so an order's discount travels as a
     * single-use Stripe coupon for the exact amount, named after the code.
     */
    private function oneTimeCoupon(Order $order): string
    {
        return $this->stripe->coupons->create([
            'amount_off' => $order->discount,
            'currency' => 'usd',
            'duration' => 'once',
            'max_redemptions' => 1,
            'name' => Str::limit($order->coupon_code ?? 'Discount', 40, ''),
            'metadata' => ['order_number' => $order->number],
        ])->id;
    }

    public function closeCheckoutSession(Order $order): bool
    {
        if (blank($order->stripe_checkout_session_id)) {
            return true;
        }

        try {
            $session = $this->stripe->checkout->sessions->retrieve($order->stripe_checkout_session_id);

            return match ($session->status) {
                'open' => $this->stripe->checkout->sessions->expire($session->id)->status === 'expired',
                'expired' => true,
                default => false,
            };
        } catch (ApiErrorException $e) {
            report($e);

            return false;
        }
    }

    public function parseWebhook(string $payload, ?string $signature): WebhookEvent
    {
        try {
            $event = Webhook::constructEvent($payload, (string) $signature, $this->webhookSecret);
        } catch (SignatureVerificationException|UnexpectedValueException $e) {
            throw new InvalidWebhookSignature($e->getMessage(), previous: $e);
        }

        /** @var Session|Charge|object $object */
        $object = $event->data->object;

        /** Checkout events carry a session; charge events (refunds) only carry the payment intent. */
        $isSession = ($object->object ?? null) === 'checkout.session';

        return new WebhookEvent(
            type: $event->type,
            checkoutSessionId: $isSession ? ($object->id ?? null) : null,
            paymentIntentId: is_string($object->payment_intent ?? null) ? $object->payment_intent : null,
            paymentStatus: $object->payment_status ?? null,
            fullyRefunded: (bool) ($object->refunded ?? false),
        );
    }
}
