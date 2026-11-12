<?php

namespace App\Enums;

enum CouponScope: string
{
    case All = 'all';
    case Camps = 'camps';
    case Merch = 'merch';

    public function label(): string
    {
        return match ($this) {
            self::All => 'Camps and merch',
            self::Camps => 'Camps only',
            self::Merch => 'Merch only',
        };
    }

    public function covers(OrderType $type): bool
    {
        return match ($this) {
            self::All => true,
            self::Camps => $type === OrderType::Camp,
            self::Merch => $type === OrderType::Merch,
        };
    }
}
