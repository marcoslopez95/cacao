<?php

namespace App\Actions\Security;

use Spatie\Permission\Models\Role;

class DeleteRoleAction
{
    /**
     * Delete the role if it has no assigned users.
     *
     * Returns false when the role still has users assigned, so the controller
     * can flash the appropriate error message without throwing an exception.
     */
    public function handle(Role $role): bool
    {
        if ($role->users()->count() > 0) {
            return false;
        }

        $role->delete();

        return true;
    }
}
