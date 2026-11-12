<?php

namespace App\Enums;

enum CouponType: string
{
    case Percent = 'percent';
    case FixedOrder = 'fixed_order';
    case FixedPerItem = 'fixed_per_item';

    public function label(): string
    {
        return match ($this) {
            self::Percent => 'Percentage off',
            self::FixedOrder => 'Fixed amount off the order',
            self::FixedPerItem => 'Fixed amount off each item',
        };
    }
}
