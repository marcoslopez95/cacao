<?php

namespace App\Enums;

enum GradeScaleType: string
{
    case Numeric = 'numeric';
    case Letter = 'letter';

    public function label(): string
    {
        return match ($this) {
            self::Numeric => 'Numérica',
            self::Letter => 'Letras (A–F)',
        };
    }
}
