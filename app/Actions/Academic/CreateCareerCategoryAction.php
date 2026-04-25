<?php

namespace App\Actions\Academic;

use App\Http\Wrappers\Academic\CareerCategoryWrapper;
use App\Models\CareerCategory;

class CreateCareerCategoryAction
{
    public function handle(CareerCategoryWrapper $wrapper): CareerCategory
    {
        return CareerCategory::create(['name' => $wrapper->getName()]);
    }
}
