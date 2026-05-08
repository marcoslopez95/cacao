<?php

namespace App\Enums;

enum DayOfWeek: string
{
    case Monday    = 'monday';
    case Tuesday   = 'tuesday';
    case Wednesday = 'wednesday';
    case Thursday  = 'thursday';
    case Friday    = 'friday';
    case Saturday  = 'saturday';

    public function label(): string
    {
        return match ($this) {
            self::Monday    => 'Lunes',
            self::Tuesday   => 'Martes',
            self::Wednesday => 'Miércoles',
            self::Thursday  => 'Jueves',
            self::Friday    => 'Viernes',
            self::Saturday  => 'Sábado',
        };
    }

    public function order(): int
    {
        return match ($this) {
            self::Monday    => 1,
            self::Tuesday   => 2,
            self::Wednesday => 3,
            self::Thursday  => 4,
            self::Friday    => 5,
            self::Saturday  => 6,
        };
    }
}
