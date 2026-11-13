<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Exceptions\InvalidWebhookSignature;
use App\Models\Order;
use App\Support\Payments\CheckoutSession;
use App\Support\Payments\WebhookEvent;
use RuntimeException;

/**
 * Used outside local development and tests when no Stripe secret key is
 * configured. Payments are switched off rather than silently falling back to
 * FakeGateway, which would accept sign-ups and hold camp spots with no real
 * payment page. Callers check isAvailable() before creating an order.
 */
class UnavailableGateway implements PaymentGateway
{
    public function isAvailable(): bool
    {
        return false;
    }

    public function createCheckoutSession(Order $order, string $successUrl, string $cancelUrl): CheckoutSession
    {
        throw new RuntimeException('Online payments are not configured.');
    }

    /**
     * Without Stripe there is no page to close, so nothing can be confirmed as closed.
     */
    public function closeCheckoutSession(Order $order): bool
    {
        return false;
    }

    public function refund(Order $order, int $amount): void
    {
        throw new RuntimeException('Online payments are not configured.');
    }

    public function parseWebhook(string $payload, ?string $signature): WebhookEvent
    {
        throw new InvalidWebhookSignature('Online payments are not configured.');
    }
}
