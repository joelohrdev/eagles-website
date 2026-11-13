<?php

namespace App\Support\Camps;

use App\Models\Coupon;
use App\Support\Coupons\CouponCalculator;
use App\Support\Coupons\DiscountableLine;

/**
 * The one place a camp booking is priced. Used for the live price on the
 * registration form, again when the registration is saved (never trusting a
 * total from the browser), and for the price preview in the coupon editor.
 *
 * All amounts are whole cents. A percent coupon is rounded once, on the amount
 * it applies to, and no discount can take the total below $0.
 */
final class CampPricing
{
    /**
     * @param  list<CampQuoteLine>  $lines  The sessions (or single days) picked.
     * @param  int  $sessionCount  How many sessions the camp has in all.
     * @param  int|null  $bundlePrice  The camp's multi-session price, in cents.
     */
    public static function quote(array $lines, int $sessionCount, ?int $bundlePrice, ?Coupon $coupon): CampQuote
    {
        $subtotal = array_sum(array_map(fn (CampQuoteLine $line): int => $line->price, $lines));
        $bundleDiscount = self::bundleDiscount($lines, $sessionCount, $bundlePrice, $subtotal);
        $stacks = $coupon !== null && $coupon->combinable_with_multi_session;

        return new CampQuote(
            lines: $lines,
            subtotal: $subtotal,
            bundleDiscount: $bundleDiscount,
            coupon: $coupon,
            couponDiscount: match (true) {
                $coupon === null => 0,
                /**
                 * Combinable: the multi-session price comes off FIRST, then the
                 * coupon is worked out on what is left (e.g. 20% of $225, not of $250).
                 */
                $stacks => min(
                    CouponCalculator::discount($coupon, self::linesAfter($lines, $bundleDiscount)),
                    $subtotal - $bundleDiscount,
                ),
                /** Not combinable: the coupon is worked out on the full prices; CampQuote then keeps the better of the two. */
                default => CouponCalculator::discount($coupon, self::linesAfter($lines, 0)),
            },
            couponStacks: $stacks,
        );
    }

    /**
     * The saving from the multi-session price, which applies only when every
     * day of every session is picked.
     *
     * @param  list<CampQuoteLine>  $lines
     */
    private static function bundleDiscount(array $lines, int $sessionCount, ?int $bundlePrice, int $subtotal): int
    {
        $everything = count($lines) === $sessionCount
            && collect($lines)->every(fn (CampQuoteLine $line): bool => $line->wholeSession);

        if (! $everything || $bundlePrice === null || $sessionCount < 2) {
            return 0;
        }

        return max(0, $subtotal - $bundlePrice);
    }

    /**
     * Each line's price with its share of a discount already taken off, spread
     * in proportion to price (the last line takes the rounding remainder).
     *
     * @param  list<CampQuoteLine>  $lines
     * @return list<DiscountableLine>
     */
    private static function linesAfter(array $lines, int $discount): array
    {
        $subtotal = array_sum(array_map(fn (CampQuoteLine $line): int => $line->price, $lines));
        $remaining = $discount;
        $discountable = [];

        foreach ($lines as $index => $line) {
            $share = $index === count($lines) - 1 || $subtotal === 0
                ? $remaining
                : intdiv($discount * $line->price, $subtotal);
            $remaining -= $share;
            $discountable[] = new DiscountableLine(max(0, $line->price - $share));
        }

        return $discountable;
    }
}
