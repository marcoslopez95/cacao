<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view the list of users.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    /**
     * Determine whether the user can create users.
     */
    public function create(User $user): bool
    {
        return $user->can('users.create');
    }

    /**
     * Determine whether the user can update the given user.
     * An admin cannot edit their own account via this module.
     */
    public function update(User $user, User $target): bool
    {
        if ($user->id === $target->id) {
            return false;
        }

        return $user->can('users.update');
    }

    /**
     * Determine whether the user can delete the given user.
     */
    public function delete(User $user, User $target): bool
    {
        return $user->can('users.delete');
    }

    /**
     * Determine whether the user can activate or deactivate the given user.
     * A user cannot deactivate their own account.
     */
    public function deactivate(User $user, User $target): bool
    {
        if ($user->id === $target->id) {
            return false;
        }

        return $user->can('users.deactivate');
    }

    /**
     * Determine whether the user can reset another user's password.
     */
    public function resetPassword(User $user): bool
    {
        return $user->can('users.reset-password');
    }

    /**
     * Determine whether the user can send invitations.
     */
    public function invite(User $user): bool
    {
        return $user->can('users.invite');
    }
}
