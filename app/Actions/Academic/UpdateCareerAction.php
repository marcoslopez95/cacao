<?php

namespace App\Actions\Academic;

use App\Http\Wrappers\Academic\CareerWrapper;
use App\Models\Career;

class UpdateCareerAction
{
    public function handle(Career $career, CareerWrapper $wrapper): Career
    {
        $career->update([
            'career_category_id' => $wrapper->getCategoryId(),
            'name' => $wrapper->getName(),
            'code' => $wrapper->getCode(),
            'active' => $wrapper->isActive(),
        ]);

        return $career;
    }
}
