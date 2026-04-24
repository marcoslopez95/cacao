<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    public function create(User $user): bool
    {
        return $user->can('users.create');
    }

    public function update(User $user, User $target): bool
    {
        if ($user->id === $target->id) {
            return false;
        }

        return $user->can('users.update');
    }

    public function delete(User $user, User $target): bool
    {
        return $user->can('users.delete');
    }

    public function deactivate(User $user, User $target): bool
    {
        if ($user->id === $target->id) {
            return false;
        }

        return $user->can('users.deactivate');
    }

    public function resetPassword(User $user): bool
    {
        return $user->can('users.reset-password');
    }

    public function invite(User $user): bool
    {
        return $user->can('users.invite');
    }
}
