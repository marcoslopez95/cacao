<?php

namespace App\Actions\Security;

use App\Http\Wrappers\Security\UserWrapper;
use App\Models\User;
use Illuminate\Support\Facades\Password;

class CreateUserAction
{
    /**
     * Create a new user, assign their role, and optionally send a password reset link.
     */
    public function handle(UserWrapper $wrapper): User
    {
        $user = User::create($wrapper->getStoreData());

        $user->syncRoles([$wrapper->getRoleName()]);

        if ($wrapper->sendsResetLink()) {
            Password::sendResetLink(['email' => $wrapper->getEmail()]);
        }

        return $user;
    }
}
