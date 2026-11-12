<?php

namespace App\Support\Camps;

use App\Models\Coupon;

/**
 * What a camp selection costs. A coupon and the multi-session (bundle) price are
 * never combined: the bigger of the two discounts is the one applied.
 */
final readonly class CampQuote
{
    public const string BUNDLE_LABEL = 'Multi-session price';

    /**
     * @param  list<CampQuoteLine>  $lines
     */
    public function __construct(
        public array $lines,
        public int $subtotal,
        public int $bundleDiscount,
        public ?Coupon $coupon,
        public int $couponDiscount,
    ) {}

    public function couponApplies(): bool
    {
        return $this->coupon !== null && $this->couponDiscount > 0 && $this->couponDiscount >= $this->bundleDiscount;
    }

    /**
     * The coupon that ends up on the order, or null when the bundle price wins.
     */
    public function appliedCoupon(): ?Coupon
    {
        return $this->couponApplies() ? $this->coupon : null;
    }

    public function discount(): int
    {
        return max($this->bundleDiscount, $this->couponDiscount);
    }

    public function discountLabel(): ?string
    {
        if ($this->discount() === 0) {
            return null;
        }

        return $this->couponApplies() ? $this->coupon->code : self::BUNDLE_LABEL;
    }

    public function total(): int
    {
        return $this->subtotal - $this->discount();
    }

    /**
     * Shown under the coupon field when a code is valid but the bundle price beats it.
     */
    public function couponNote(): ?string
    {
        if ($this->coupon === null || $this->couponApplies()) {
            return null;
        }

        return __('Your multi-session price is already lower, so this code isn’t needed.');
    }

    /**
     * @return array{lines: list<array<string, mixed>>, subtotal: int, discount: int, discount_label: string|null, coupon_code: string|null, coupon_note: string|null, total: int}
     */
    public function toArray(): array
    {
        return [
            'lines' => array_map(fn (CampQuoteLine $line): array => $line->toArray(), $this->lines),
            'subtotal' => $this->subtotal,
            'discount' => $this->discount(),
            'discount_label' => $this->discountLabel(),
            'coupon_code' => $this->appliedCoupon()?->code,
            'coupon_note' => $this->couponNote(),
            'total' => $this->total(),
        ];
    }
}
