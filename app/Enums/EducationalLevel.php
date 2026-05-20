<?php

namespace App\Enums;

enum EducationalLevel: string
{
    case Primary = 'primary';
    case Secondary = 'secondary';
    case University = 'university';

    public function label(): string
    {
        return match ($this) {
            self::Primary => 'Primaria',
            self::Secondary => 'Secundaria',
            self::University => 'Universidad',
        };
    }
}
