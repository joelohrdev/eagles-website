<?php

namespace App\Support\Camps;

use App\Models\Coupon;

/**
 * What a camp selection costs, built by CampPricing. A combinable coupon is
 * taken off after the multi-session (bundle) price; any other coupon competes
 * with it, and only the bigger saving is applied (the multi-session price wins a tie).
 */
final readonly class CampQuote
{
    public const string BUNDLE_LABEL = 'Multi-session price';

    /**
     * @param  list<CampQuoteLine>  $lines
     * @param  bool  $couponStacks  The coupon is combinable with the multi-session price.
     */
    public function __construct(
        public array $lines,
        public int $subtotal,
        public int $bundleDiscount,
        public ?Coupon $coupon,
        public int $couponDiscount,
        public bool $couponStacks = false,
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
        if ($this->coupon === null || $this->couponDiscount === 0) {
            return false;
        }

        return $this->couponStacks || $this->couponDiscount > $this->bundleDiscount;
    }

    /**
     * Whether the multi-session saving is taken; a non-combinable coupon that beats it replaces it.
     */
    public function bundleApplies(): bool
    {
        return $this->bundleDiscount > 0 && ($this->couponStacks || ! $this->couponApplies());
    }

    /**
     * The coupon that ends up on the order, or null when it isn't used.
     */
    public function appliedCoupon(): ?Coupon
    {
        return $this->couponApplies() ? $this->coupon : null;
    }

    /**
     * Each discount actually taken off, in the order applied.
     *
     * @return list<array{label: string, amount: int}>
     */
    public function discounts(): array
    {
        $discounts = [];

        if ($this->bundleApplies()) {
            $discounts[] = ['label' => self::BUNDLE_LABEL, 'amount' => $this->bundleDiscount];
        }

        if ($this->couponApplies()) {
            $discounts[] = ['label' => $this->coupon->code, 'amount' => $this->couponDiscount];
        }

        return $discounts;
    }

    public function discount(): int
    {
        return array_sum(array_column($this->discounts(), 'amount'));
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
        return max(0, $this->subtotal - $this->discount());
    }

    /**
     * Shown under the coupon field when a valid code isn't used: either the
     * multi-session price saves more, or the code takes nothing off.
     */
    public function couponNote(): ?string
    {
        if ($this->coupon === null || $this->couponApplies()) {
            return null;
        }

        return $this->bundleApplies()
            ? __('Multi-session discount applied — it’s a better deal than your coupon.')
            : __('This code doesn’t take anything off these sessions.');
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
