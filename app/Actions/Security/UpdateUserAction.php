<?php

namespace App\Actions\Security;

use App\Http\Wrappers\Security\UserWrapper;
use App\Models\User;

class UpdateUserAction
{
    /**
     * Update the user's profile and sync their roles.
     */
    public function handle(User $user, UserWrapper $wrapper): User
    {
        $user->update($wrapper->getUpdateData());

        $user->syncRoles($wrapper->getRoles());

        return $user;
    }
}
