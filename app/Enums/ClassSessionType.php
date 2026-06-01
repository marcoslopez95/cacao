<?php

namespace App\Enums;

enum ClassSessionType: string
{
    case Regular = 'regular';
    case Makeup = 'makeup';
    case Advance = 'advance';

    public function label(): string
    {
        return match ($this) {
            self::Regular => 'Regular',
            self::Makeup => 'Recuperación',
            self::Advance => 'Adelanto',
        };
    }
}
