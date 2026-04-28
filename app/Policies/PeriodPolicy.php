<?php

namespace App\Policies;

use App\Models\Period;
use App\Models\User;

class PeriodPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('periods.view');
    }

    public function create(User $user): bool
    {
        return $user->can('periods.create');
    }

    public function update(User $user, Period $period): bool
    {
        return $user->can('periods.update');
    }

    public function delete(User $user, Period $period): bool
    {
        return $user->can('periods.delete');
    }
}
