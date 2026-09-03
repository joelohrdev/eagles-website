<?php

namespace App\Enums;

enum OrderType: string
{
    case Merch = 'merch';
    case Camp = 'camp';

    public function label(): string
    {
        return match ($this) {
            self::Merch => 'Merch',
            self::Camp => 'Camp registration',
        };
    }
}
