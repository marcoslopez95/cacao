<?php

namespace App\Policies\Academic;

use App\Models\CareerCategory;
use App\Models\User;

class CareerCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('career-categories.view');
    }

    public function create(User $user): bool
    {
        return $user->can('career-categories.create');
    }

    public function update(User $user, CareerCategory $careerCategory): bool
    {
        return $user->can('career-categories.update');
    }

    public function delete(User $user, CareerCategory $careerCategory): bool
    {
        return $user->can('career-categories.delete');
    }
}
