<?php

namespace App\Enums;

enum PeriodType: string
{
    case Semester = 'semester';
    case Year = 'year';
    case Trimester = 'trimester';

    public function label(): string
    {
        return match ($this) {
            self::Semester => 'Semestral',
            self::Year => 'Anual',
            self::Trimester => 'Trimestral',
        };
    }
}
