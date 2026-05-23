<?php

namespace App\Enums;

enum GradeLevel: string
{
    case PrimarySecondary = 'primary_secondary';
    case University = 'university';

    public function label(): string
    {
        return match ($this) {
            self::PrimarySecondary => 'Primaria / Secundaria',
            self::University => 'Universitario',
        };
    }

    public function usesLapses(): bool
    {
        return $this === self::PrimarySecondary;
    }
}
