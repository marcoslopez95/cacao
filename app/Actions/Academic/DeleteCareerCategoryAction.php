<?php

namespace App\Actions\Academic;

use App\Models\CareerCategory;

class DeleteCareerCategoryAction
{
    /**
     * Delete the category if it has no associated careers.
     * Returns false when deletion is blocked.
     */
    public function handle(CareerCategory $careerCategory): bool
    {
        if ($careerCategory->careers()->exists()) {
            return false;
        }

        $careerCategory->delete();

        return true;
    }
}
