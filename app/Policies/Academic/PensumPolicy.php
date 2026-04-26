<?php

namespace App\Policies\Academic;

use App\Models\Pensum;
use App\Models\User;

class PensumPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('pensums.view');
    }

    public function create(User $user): bool
    {
        return $user->can('pensums.create');
    }

    public function update(User $user, Pensum $pensum): bool
    {
        return $user->can('pensums.update');
    }

    public function delete(User $user, Pensum $pensum): bool
    {
        return $user->can('pensums.delete');
    }
}
