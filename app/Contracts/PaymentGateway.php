<?php

namespace App\Contracts;

use App\Exceptions\InvalidWebhookSignature;
use App\Models\Order;
use App\Support\Payments\CheckoutSession;
use App\Support\Payments\WebhookEvent;

interface PaymentGateway
{
    /**
     * Create a hosted checkout session for the order and return its id + redirect URL.
     */
    public function createCheckoutSession(Order $order, string $successUrl, string $cancelUrl): CheckoutSession;

    /**
     * Verify a webhook payload's signature and normalize it into a WebhookEvent.
     *
     * @throws InvalidWebhookSignature
     */
    public function parseWebhook(string $payload, ?string $signature): WebhookEvent;
}
