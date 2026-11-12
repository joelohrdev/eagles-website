<?php

use App\Models\Coupon;
use App\Support\Coupons\CouponCalculator;
use App\Support\Coupons\DiscountableLine;

test('a percent coupon takes a rounded share of the lines', function () {
    $coupon = Coupon::factory()->percent(15)->make();

    expect(CouponCalculator::discount($coupon, [new DiscountableLine(12500), new DiscountableLine(999, 3)]))
        ->toBe((int) round((12500 + 2997) * 0.15));
});

test('a fixed order coupon never takes off more than the order is worth', function () {
    expect(CouponCalculator::discount(Coupon::factory()->fixedOrder(3000)->make(), [new DiscountableLine(12500)]))->toBe(3000)
        ->and(CouponCalculator::discount(Coupon::factory()->fixedOrder(3000)->make(), [new DiscountableLine(2000)]))->toBe(2000);
});

test('a per item coupon applies to every unit and is capped at each unit price', function () {
    $coupon = Coupon::factory()->fixedPerItem(2500)->make();

    expect(CouponCalculator::discount($coupon, [new DiscountableLine(12500), new DiscountableLine(12500)]))->toBe(5000)
        ->and(CouponCalculator::discount($coupon, [new DiscountableLine(2000, 2)]))->toBe(4000)
        ->and(CouponCalculator::discount($coupon, [new DiscountableLine(3000, 2)]))->toBe(5000);
});
