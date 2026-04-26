<?php

namespace App\Policies\Academic;

use App\Models\Career;
use App\Models\User;

class CareerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('careers.view');
    }

    public function create(User $user): bool
    {
        return $user->can('careers.create');
    }

    public function update(User $user, Career $career): bool
    {
        return $user->can('careers.update');
    }

    public function delete(User $user, Career $career): bool
    {
        return $user->can('careers.delete');
    }
}
