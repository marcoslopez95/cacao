<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'Admin';
    case Professor = 'Profesor';
    case Student = 'Estudiante';
    case Coordinator = 'Coordinador de Area';

    /**
     * Get the display label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Professor => 'Profesor',
            self::Student => 'Estudiante',
            self::Coordinator => 'Coordinador de Área',
        };
    }

    /**
     * Get all role names as a flat array of strings.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
