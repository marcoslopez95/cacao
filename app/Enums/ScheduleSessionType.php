<?php

namespace App\Enums;

enum ScheduleSessionType: string
{
    case Theory = 'theory';
    case Lab    = 'lab';

    public function label(): string
    {
        return match ($this) {
            self::Theory => 'Teoría',
            self::Lab    => 'Laboratorio',
        };
    }
}
