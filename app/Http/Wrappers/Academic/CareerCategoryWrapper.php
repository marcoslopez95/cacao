<?php

namespace App\Http\Wrappers\Academic;

use Illuminate\Support\Collection;

class CareerCategoryWrapper extends Collection
{
    public function getName(): string
    {
        return $this->get('name');
    }
}
