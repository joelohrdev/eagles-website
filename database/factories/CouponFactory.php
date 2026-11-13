<?php

namespace Database\Factories;

use App\Enums\CouponScope;
use App\Enums\CouponType;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => Str::upper(fake()->unique()->bothify('SAVE##??')),
            'description' => null,
            'type' => CouponType::Percent,
            'value' => 10,
            'applies_to' => CouponScope::All,
            'combinable_with_multi_session' => false,
            'starts_at' => null,
            'expires_at' => null,
            'max_redemptions' => null,
            'is_active' => true,
        ];
    }

    public function percent(int $percent): static
    {
        return $this->state(fn () => ['type' => CouponType::Percent, 'value' => $percent]);
    }

    public function fixedOrder(int $cents): static
    {
        return $this->state(fn () => ['type' => CouponType::FixedOrder, 'value' => $cents]);
    }

    public function fixedPerItem(int $cents): static
    {
        return $this->state(fn () => ['type' => CouponType::FixedPerItem, 'value' => $cents]);
    }

    /**
     * Stacks on a camp's multi-session price instead of competing with it.
     */
    public function combinable(): static
    {
        return $this->state(fn () => ['combinable_with_multi_session' => true]);
    }

    public function scope(CouponScope $scope): static
    {
        return $this->state(fn () => ['applies_to' => $scope]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function expired(): static
    {
        return $this->state(fn () => ['expires_at' => now()->subDay()]);
    }
}
