<?php

namespace App\Http\Controllers\Security;

use App\Actions\Security\CreateRoleAction;
use App\Actions\Security\DeleteRoleAction;
use App\Actions\Security\UpdateRoleAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Security\StoreRoleRequest;
use App\Http\Requests\Security\UpdateRoleRequest;
use App\Http\Resources\Security\RoleResource;
use App\Http\Wrappers\Security\RoleWrapper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            ->get();

        return Inertia::render('security/Roles/Index', [
            'roles' => RoleResource::collection($roles)->resolve(),
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
    public function store(StoreRoleRequest $request, CreateRoleAction $action): RedirectResponse
    {
        $action->handle(new RoleWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Rol creado.']);

        return to_route('security.roles.index');
    }

    /**
     * Update the specified role.
     */
    public function update(UpdateRoleRequest $request, Role $role, UpdateRoleAction $action): RedirectResponse
    {
        $action->handle($role, new RoleWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Rol actualizado.']);

        return to_route('security.roles.index');
    }

    /**
     * Delete the specified role.
     */
    public function destroy(Role $role, DeleteRoleAction $action): RedirectResponse
    {
        abort_if($role->name === 'Admin', 403);

        Gate::authorize('delete', $role);

        if (! $action->handle($role)) {
            $usersCount = $role->users()->count();
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => "No se puede eliminar: el rol tiene {$usersCount} usuarios asignados.",
            ]);

            return to_route('security.roles.index');
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Rol eliminado.']);

        return to_route('security.roles.index');
    }
}
