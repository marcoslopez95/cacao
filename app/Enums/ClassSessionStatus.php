<?php

namespace App\Enums;

enum ClassSessionStatus: string
{
    case Scheduled = 'scheduled';
    case Held = 'held';
    case Cancelled = 'cancelled';
    case Recovered = 'recovered';
    case Advanced = 'advanced';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Pendiente',
            self::Held => 'Dada',
            self::Cancelled => 'Cancelada',
            self::Recovered => 'Recuperada',
            self::Advanced => 'Adelantada',
        };
    }
}
