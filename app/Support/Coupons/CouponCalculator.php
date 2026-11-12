<?php

namespace App\Support\Coupons;

use App\Enums\CouponType;
use App\Models\Coupon;

/**
 * Works out how many cents a coupon takes off a set of lines. The result is
 * never more than the lines are worth, so an order total can't go negative.
 */
final class CouponCalculator
{
    /**
     * @param  list<DiscountableLine>  $lines
     */
    public static function discount(Coupon $coupon, array $lines): int
    {
        $subtotal = array_sum(array_map(fn (DiscountableLine $line): int => $line->total(), $lines));

        $discount = match ($coupon->type) {
            CouponType::Percent => (int) round($subtotal * min($coupon->value, 100) / 100),
            CouponType::FixedOrder => $coupon->value,
            CouponType::FixedPerItem => array_sum(array_map(
                fn (DiscountableLine $line): int => min($coupon->value, $line->unitPrice) * $line->quantity,
                $lines,
            )),
        };

        return max(0, min($discount, $subtotal));
    }
}
