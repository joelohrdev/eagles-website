<?php

namespace App\Contracts;

use App\Exceptions\InvalidWebhookSignature;
use App\Models\Order;
use App\Support\Payments\CheckoutSession;
use App\Support\Payments\WebhookEvent;

interface PaymentGateway
{
    /**
     * Whether online payment can be taken right now. Check before creating a
     * paid order, so nothing is held for a payment page that can't exist.
     */
    public function isAvailable(): bool;

    /**
     * Create a hosted checkout session for the order and return its id + redirect URL.
     */
    public function createCheckoutSession(Order $order, string $successUrl, string $cancelUrl): CheckoutSession;

    /**
     * Close the order's hosted checkout page so it can no longer be paid.
     * Returns false when the page was already paid (or the gateway can't be
     * reached), so the caller must not release the order: its payment will be
     * confirmed by webhook.
     */
    public function closeCheckoutSession(Order $order): bool;

    /**
     * Verify a webhook payload's signature and normalize it into a WebhookEvent.
     *
     * @throws InvalidWebhookSignature
     */
    public function parseWebhook(string $payload, ?string $signature): WebhookEvent;
}
