<?php

namespace App\Enums;

enum EnrollmentDetailStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Confirmed => 'Confirmada',
            self::Rejected => 'Rechazada',
        };
    }
}
