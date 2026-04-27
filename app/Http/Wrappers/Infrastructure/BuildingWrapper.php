<?php

namespace App\Http\Wrappers\Infrastructure;

use Illuminate\Support\Collection;

class BuildingWrapper extends Collection
{
    public function getName(): string
    {
        return (string) $this->get('name');
    }
}
