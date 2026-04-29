<?php

namespace App\Enums;

enum SectionType: string
{
    case University = 'university';
    case School     = 'school';

    public function label(): string
    {
        return match ($this) {
            self::University => 'Universitaria',
            self::School     => 'Escolar',
        };
    }
}
