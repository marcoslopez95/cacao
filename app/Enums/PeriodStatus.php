<?php

namespace App\Enums;

enum PeriodStatus: string
{
    case Upcoming = 'upcoming';
    case Active = 'active';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Upcoming => 'Próximo',
            self::Active => 'Activo',
            self::Closed => 'Cerrado',
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Upcoming => $next === self::Active,
            self::Active => $next === self::Closed,
            self::Closed => false,
        };
    }
}
