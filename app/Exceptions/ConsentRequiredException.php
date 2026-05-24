<?php

namespace App\Exceptions;

use RuntimeException;

class ConsentRequiredException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Se requiere consentimiento de tratamiento de datos para continuar.');
    }
}
