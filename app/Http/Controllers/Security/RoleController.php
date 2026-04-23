<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Http\Requests\Security\StoreRoleRequest;
use App\Http\Requests\Security\UpdateRoleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display the list of roles.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Role::class);

        $user = $request->user();

        $roles = Role::query()
            ->withCount('users')
            ->with('permissions:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'isAdmin' => $role->name === 'Admin',
                'usersCount' => $role->users_count,
                'permissions' => $role->permissions->pluck('name')->values(),
            ])
            ->values();

        return Inertia::render('security/Roles/Index', [
            'roles' => $roles,
            'permissions' => Permission::orderBy('name')->pluck('name')->values(),
            'can' => [
                'create' => $user->can('roles.create'),
                'update' => $user->can('roles.update'),
                'delete' => $user->can('roles.delete'),
                'assignPermissions' => $user->can('roles.assign-permissions'),
            ],
        ]);
    }

    /**
     * Store a newly created role.
     */
    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data) {
            $role = Role::create([
                'name' => $data['name'],
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($data['permissions'] ?? []);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Rol creado.']);

        return to_route('security.roles.index');
    }

    /**
     * Update the specified role.
     */
    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $role) {
            $role->update(['name' => $data['name']]);
            $role->syncPermissions($data['permissions'] ?? []);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Rol actualizado.']);

        return to_route('security.roles.index');
    }

    /**
     * Delete the specified role.
     */
    public function destroy(Role $role): RedirectResponse
    {
        abort_if($role->name === 'Admin', 403);

        Gate::authorize('delete', $role);

        $usersCount = $role->users()->count();

        if ($usersCount > 0) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => "No se puede eliminar: el rol tiene {$usersCount} usuarios asignados.",
            ]);

            return to_route('security.roles.index');
        }

        $role->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Rol eliminado.']);

        return to_route('security.roles.index');
    }
}
