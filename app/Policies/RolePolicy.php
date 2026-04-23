<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    /**
     * Determine whether the user can view the list of roles.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('roles.view');
    }

    /**
     * Determine whether the user can create roles.
     */
    public function create(User $user): bool
    {
        return $user->can('roles.create');
    }

    /**
     * Determine whether the user can update the given role.
     */
    public function update(User $user, Role $role): bool
    {
        if ($this->isProtected($role)) {
            return false;
        }

        return $user->can('roles.update');
    }

    /**
     * Determine whether the user can delete the given role.
     */
    public function delete(User $user, Role $role): bool
    {
        if ($this->isProtected($role)) {
            return false;
        }

        return $user->can('roles.delete');
    }

    /**
     * Determine whether the user can assign permissions to the given role.
     */
    public function assignPermissions(User $user, Role $role): bool
    {
        if ($this->isProtected($role)) {
            return false;
        }

        return $user->can('roles.assign-permissions');
    }

    /**
     * The Admin role cannot be mutated from the UI.
     */
    protected function isProtected(Role $role): bool
    {
        return $role->name === 'Admin';
    }
}
