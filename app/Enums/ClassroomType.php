<?php

namespace App\Enums;

enum ClassroomType: string
{
    case Theory     = 'theory';
    case Laboratory = 'laboratory';

    public function label(): string
    {
        return match ($this) {
            self::Theory     => 'Teórica',
            self::Laboratory => 'Laboratorio',
        };
    }
}
