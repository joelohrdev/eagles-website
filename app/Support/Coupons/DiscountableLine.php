<?php

namespace App\Support\Coupons;

/**
 * One priced line a coupon can discount: a camp session, or a merch item.
 */
final readonly class DiscountableLine
{
    public function __construct(
        public int $unitPrice,
        public int $quantity = 1,
    ) {}

    public function total(): int
    {
        return $this->unitPrice * $this->quantity;
    }
}
