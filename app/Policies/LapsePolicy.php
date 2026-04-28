<?php

namespace App\Policies;

use App\Models\Lapse;
use App\Models\User;

class LapsePolicy
{
    public function create(User $user): bool
    {
        return $user->can('lapses.create');
    }

    public function update(User $user, Lapse $lapse): bool
    {
        return $user->can('lapses.update');
    }

    public function delete(User $user, Lapse $lapse): bool
    {
        return $user->can('lapses.delete');
    }
}
