<?php

namespace App\Enums;

enum EnrollmentStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Confirmed => 'Confirmada',
            self::Approved => 'Aprobada',
            self::Rejected => 'Rechazada',
        };
    }
}
