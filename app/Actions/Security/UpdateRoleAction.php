<?php

namespace App\Actions\Security;

use App\Http\Wrappers\Security\RoleWrapper;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class UpdateRoleAction
{
    /**
     * Update the role name and sync its permissions.
     */
    public function handle(Role $role, RoleWrapper $wrapper): Role
    {
        return DB::transaction(function () use ($role, $wrapper): Role {
            $role->update(['name' => $wrapper->getName()]);

            $role->syncPermissions($wrapper->getPermissions());

            return $role;
        });
    }
}
