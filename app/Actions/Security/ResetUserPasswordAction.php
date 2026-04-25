<?php

namespace App\Actions\Security;

use App\Http\Wrappers\Security\UserWrapper;
use App\Models\User;
use Illuminate\Support\Facades\Password;

class ResetUserPasswordAction
{
    /**
     * Send a password reset link or update the user's password directly.
     */
    public function handle(User $user, UserWrapper $wrapper): void
    {
        if ($wrapper->sendsResetLink()) {
            Password::sendResetLink(['email' => $user->email]);

            return;
        }

        $user->update(['password' => $wrapper->getHashedPassword()]);
    }
}
