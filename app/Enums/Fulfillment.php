<?php

namespace App\Enums;

enum Fulfillment: string
{
    case Pickup = 'pickup';
    case Shipping = 'shipping';

    public function label(): string
    {
        return match ($this) {
            self::Pickup => 'Local pickup',
            self::Shipping => 'Ship to address',
        };
    }
}
