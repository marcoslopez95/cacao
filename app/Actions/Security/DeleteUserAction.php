<?php

namespace App\Actions\Security;

use App\Models\User;

class DeleteUserAction
{
    /**
     * Delete the given user.
     */
    public function handle(User $user): void
    {
        $user->delete();
    }
}
