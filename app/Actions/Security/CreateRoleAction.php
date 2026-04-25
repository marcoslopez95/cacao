<?php

namespace App\Actions\Security;

use App\Http\Wrappers\Security\RoleWrapper;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class CreateRoleAction
{
    /**
     * Create a new role and assign the given permissions.
     */
    public function handle(RoleWrapper $wrapper): Role
    {
        return DB::transaction(function () use ($wrapper): Role {
            $role = Role::create($wrapper->getStoreData());

            $role->syncPermissions($wrapper->getPermissions());

            return $role;
        });
    }
}
