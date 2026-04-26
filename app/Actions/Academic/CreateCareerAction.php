<?php

namespace App\Actions\Academic;

use App\Http\Wrappers\Academic\CareerWrapper;
use App\Models\Career;

class CreateCareerAction
{
    public function handle(CareerWrapper $wrapper): Career
    {
        return Career::create([
            'career_category_id' => $wrapper->getCategoryId(),
            'name' => $wrapper->getName(),
            'code' => $wrapper->getCode(),
            'active' => $wrapper->isActive(),
        ]);
    }
}
