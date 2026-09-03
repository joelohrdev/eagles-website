<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Exceptions\InvalidWebhookSignature;
use App\Models\Order;
use App\Support\Payments\CheckoutSession;
use App\Support\Payments\WebhookEvent;
use Illuminate\Support\Str;

/**
 * In-memory gateway for tests and local development without Stripe keys.
 * Webhook payloads are plain JSON: {"type": "...", "session_id": "...", "payment_intent": "...", "payment_status": "paid"}
 * with the signature header equal to the literal string "fake".
 */
class FakeGateway implements PaymentGateway
{
    /** @var list<array{order_id: int, session_id: string}> */
    public array $sessions = [];

    public function createCheckoutSession(Order $order, string $successUrl, string $cancelUrl): CheckoutSession
    {
        $id = 'cs_fake_'.Str::random(16);
        $this->sessions[] = ['order_id' => $order->id, 'session_id' => $id];

        return new CheckoutSession(id: $id, url: $successUrl);
    }

    public function parseWebhook(string $payload, ?string $signature): WebhookEvent
    {
        if ($signature !== 'fake') {
            throw new InvalidWebhookSignature('Invalid fake signature.');
        }

        $data = json_decode($payload, true) ?: [];

        return new WebhookEvent(
            type: $data['type'] ?? 'unknown',
            checkoutSessionId: $data['session_id'] ?? null,
            paymentIntentId: $data['payment_intent'] ?? null,
            paymentStatus: $data['payment_status'] ?? null,
        );
    }
}
