<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Exceptions\InvalidWebhookSignature;
use App\Models\Order;
use App\Models\OrderItem;
use App\Support\Payments\CheckoutSession;
use App\Support\Payments\WebhookEvent;
use Stripe\Checkout\Session;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeGateway implements PaymentGateway
{
    public function __construct(private StripeClient $stripe, private string $webhookSecret) {}

    public function createCheckoutSession(Order $order, string $successUrl, string $cancelUrl): CheckoutSession
    {
        $order->loadMissing('items');

        $session = $this->stripe->checkout->sessions->create([
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

    public function parseWebhook(string $payload, ?string $signature): WebhookEvent
    {
        try {
            $event = Webhook::constructEvent($payload, (string) $signature, $this->webhookSecret);
        } catch (SignatureVerificationException|UnexpectedValueException $e) {
            throw new InvalidWebhookSignature($e->getMessage(), previous: $e);
        }

        /** @var Session|object $object */
        $object = $event->data->object;

        return new WebhookEvent(
            type: $event->type,
            checkoutSessionId: $object->id ?? null,
            paymentIntentId: is_string($object->payment_intent ?? null) ? $object->payment_intent : null,
            paymentStatus: $object->payment_status ?? null,
        );
    }
}
