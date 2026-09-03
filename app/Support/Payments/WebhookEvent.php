<?php

namespace App\Support\Payments;

final readonly class WebhookEvent
{
    public const string CHECKOUT_COMPLETED = 'checkout.session.completed';

    public const string CHECKOUT_EXPIRED = 'checkout.session.expired';

    public function __construct(
        public string $type,
        public ?string $checkoutSessionId,
        public ?string $paymentIntentId = null,
        public ?string $paymentStatus = null,
    ) {}

    public function isCheckoutCompleted(): bool
    {
        return $this->type === self::CHECKOUT_COMPLETED && $this->paymentStatus === 'paid';
    }

    public function isCheckoutExpired(): bool
    {
        return $this->type === self::CHECKOUT_EXPIRED;
    }
}
