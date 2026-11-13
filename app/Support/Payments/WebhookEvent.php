<?php

namespace App\Support\Payments;

final readonly class WebhookEvent
{
    public const string CHECKOUT_COMPLETED = 'checkout.session.completed';

    public const string CHECKOUT_EXPIRED = 'checkout.session.expired';

    public const string CHARGE_REFUNDED = 'charge.refunded';

    /**
     * @param  bool  $fullyRefunded  Stripe sends charge.refunded for partial refunds too; only a full refund closes the order.
     */
    public function __construct(
        public string $type,
        public ?string $checkoutSessionId,
        public ?string $paymentIntentId = null,
        public ?string $paymentStatus = null,
        public bool $fullyRefunded = false,
        public ?int $amountRefunded = null,
    ) {}

    public function isCheckoutCompleted(): bool
    {
        return $this->type === self::CHECKOUT_COMPLETED && $this->paymentStatus === 'paid';
    }

    public function isCheckoutExpired(): bool
    {
        return $this->type === self::CHECKOUT_EXPIRED;
    }

    public function isFullyRefunded(): bool
    {
        return $this->type === self::CHARGE_REFUNDED && $this->fullyRefunded;
    }

    /**
     * A refund that gives back only part of the payment (made here or in the Stripe dashboard).
     */
    public function isPartiallyRefunded(): bool
    {
        return $this->type === self::CHARGE_REFUNDED && ! $this->fullyRefunded && $this->amountRefunded !== null;
    }

    /**
     * Whether the event can be matched to an order at all.
     */
    public function identifiesOrder(): bool
    {
        return $this->checkoutSessionId !== null || $this->paymentIntentId !== null;
    }
}
