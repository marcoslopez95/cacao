<?php

namespace App\Actions\Security;

use App\Models\User;

class DeactivateUserAction
{
    /**
     * Toggle the active state of the given user.
     */
    public function handle(User $user): User
    {
        $user->update(['active' => ! $user->active]);

        return $user;
    }
}
