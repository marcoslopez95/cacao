<?php

namespace App\Actions\Academic;

use App\Http\Wrappers\Academic\CareerCategoryWrapper;
use App\Models\CareerCategory;

class UpdateCareerCategoryAction
{
    public function handle(CareerCategory $careerCategory, CareerCategoryWrapper $wrapper): CareerCategory
    {
        $careerCategory->update(['name' => $wrapper->getName()]);

        return $careerCategory;
    }
}
