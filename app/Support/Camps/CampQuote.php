<?php

namespace App\Support\Camps;

use App\Models\Coupon;

/**
 * What a camp selection costs. The multi-session (bundle) price comes off first,
 * then any coupon is taken off what is left.
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

    /**
     * Every day picked, across all lines.
     *
     * @return list<int>
     */
    public function dayIds(): array
    {
        return array_values(array_merge(...array_map(fn (CampQuoteLine $line): array => $line->days->pluck('id')->all(), $this->lines)));
    }

    public function couponApplies(): bool
    {
        return $this->coupon !== null && $this->couponDiscount > 0;
    }

    /**
     * The coupon that ends up on the order, or null when no code takes anything off.
     */
    public function appliedCoupon(): ?Coupon
    {
        return $this->couponApplies() ? $this->coupon : null;
    }

    /**
     * Each discount taken off, in the order applied.
     *
     * @return list<array{label: string, amount: int}>
     */
    public function discounts(): array
    {
        $discounts = [];

        if ($this->bundleDiscount > 0) {
            $discounts[] = ['label' => self::BUNDLE_LABEL, 'amount' => $this->bundleDiscount];
        }

        if ($this->couponApplies()) {
            $discounts[] = ['label' => $this->coupon->code, 'amount' => $this->couponDiscount];
        }

        return $discounts;
    }

    public function discount(): int
    {
        return $this->bundleDiscount + ($this->couponApplies() ? $this->couponDiscount : 0);
    }

    /**
     * One name for everything taken off, e.g. "Multi-session price + THANKS30", stored on the order.
     */
    public function discountLabel(): ?string
    {
        $labels = array_column($this->discounts(), 'label');

        return $labels === [] ? null : implode(' + ', $labels);
    }

    public function total(): int
    {
        return $this->subtotal - $this->discount();
    }

    /**
     * Shown under the coupon field when a code is valid but takes nothing off this selection.
     */
    public function couponNote(): ?string
    {
        if ($this->coupon === null || $this->couponApplies()) {
            return null;
        }

        return __('This code doesn’t take anything off these sessions.');
    }

    /**
     * @return array{lines: list<array<string, mixed>>, subtotal: int, discounts: list<array{label: string, amount: int}>, discount: int, discount_label: string|null, coupon_code: string|null, coupon_note: string|null, total: int}
     */
    public function toArray(): array
    {
        return [
            'lines' => array_map(fn (CampQuoteLine $line): array => $line->toArray(), $this->lines),
            'subtotal' => $this->subtotal,
            'discounts' => $this->discounts(),
            'discount' => $this->discount(),
            'discount_label' => $this->discountLabel(),
            'coupon_code' => $this->appliedCoupon()?->code,
            'coupon_note' => $this->couponNote(),
            'total' => $this->total(),
        ];
    }
}
