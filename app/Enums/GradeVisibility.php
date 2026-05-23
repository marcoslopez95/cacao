<?php

namespace App\Enums;

enum GradeVisibility: string
{
    case RealTime = 'real_time';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::RealTime => 'Tiempo real',
            self::Manual => 'Publicación manual',
        };
    }
}
