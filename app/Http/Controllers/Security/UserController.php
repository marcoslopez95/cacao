<?php

namespace App\Http\Controllers\Security;

use App\Actions\Security\CreateUserAction;
use App\Actions\Security\DeactivateUserAction;
use App\Actions\Security\DeleteUserAction;
use App\Actions\Security\ResetUserPasswordAction;
use App\Actions\Security\UpdateUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Security\ResetPasswordRequest;
use App\Http\Requests\Security\StoreUserRequest;
use App\Http\Requests\Security\UpdateUserRequest;
use App\Http\Resources\Security\UserResource;
use App\Http\Wrappers\Security\UserWrapper;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a paginated list of users with optional filters.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        $actor = $request->user();

        $query = User::query()->with('roles:id,name');

        $query->when($request->input('search'), fn ($q, $s) => $q
            ->where('name', 'ilike', "%{$s}%")
            ->orWhere('email', 'ilike', "%{$s}%")
        );

        $query->when($request->input('role'), fn ($q, $r) => $q->whereHas('roles', fn ($q) => $q->where('name', $r))
        );

        $query->when($request->input('status'), function ($q, $s): void {
            match ($s) {
                'active' => $q->where('active', true),
                'inactive' => $q->where('active', false),
                default => null,
            };
        });

        $perPage = min(100, max(10, (int) $request->input('per_page', 20)));

        return Inertia::render('security/Users/Index', [
            'users' => UserResource::collection($query->orderBy('name')->paginate($perPage)),
            'roles' => Role::orderBy('name')->pluck('name')->values(),
            'filters' => $request->only('search', 'role', 'status'),
            'can' => [
                'create' => $actor->can('create', User::class),
                'invite' => $actor->can('invite', User::class),
                'update' => $actor->can('update', new User),
                'delete' => $actor->can('delete', new User),
                'deactivate' => $actor->can('deactivate', new User),
                'resetPassword' => $actor->can('resetPassword', User::class),
            ],
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request, CreateUserAction $action): RedirectResponse
    {
        $wrapper = new UserWrapper($request->validated());
        $action->handle($wrapper);
        $this->flashCreated($wrapper);

        return to_route('security.users.index');
    }

    /**
     * Update the specified user's name, email and roles.
     */
    public function update(UpdateUserRequest $request, User $user, UpdateUserAction $action): RedirectResponse
    {
        $action->handle($user, new UserWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Usuario actualizado.']);

        return to_route('security.users.index');
    }

    /**
     * Delete the specified user.
     */
    public function destroy(Request $request, User $user, DeleteUserAction $action): RedirectResponse
    {
        Gate::authorize('delete', $user);

        if (! $action->handle($user)) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'No se puede eliminar: el usuario tiene asignaciones de coordinación.',
            ]);

            return to_route('security.users.index');
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Usuario eliminado.']);

        return to_route('security.users.index');
    }

    /**
     * Toggle the active state of the specified user.
     */
    public function deactivate(Request $request, User $user, DeactivateUserAction $action): RedirectResponse
    {
        Gate::authorize('deactivate', $user);

        $updated = $action->handle($user);

        $msg = $updated->active ? 'Usuario reactivado.' : 'Usuario desactivado.';
        Inertia::flash('toast', ['type' => 'success', 'message' => $msg]);

        return to_route('security.users.index');
    }

    /**
     * Reset the specified user's password.
     */
    public function resetPassword(ResetPasswordRequest $request, User $user, ResetUserPasswordAction $action): RedirectResponse
    {
        $wrapper = new UserWrapper($request->validated());
        $action->handle($user, $wrapper);
        $this->flashPasswordReset($wrapper);

        return to_route('security.users.index');
    }

    /**
     * Flash the appropriate toast for user creation based on the password mode.
     */
    private function flashCreated(UserWrapper $wrapper): void
    {
        if ($wrapper->sendsResetLink()) {
            Inertia::flash('toast', ['type' => 'success', 'message' => 'Usuario creado. Se envió un link para establecer la contraseña.']);
        } elseif ($wrapper->getPasswordMode() === 'random') {
            Inertia::flash('toast', ['type' => 'password', 'message' => "Usuario creado. Contraseña generada: {$wrapper->getPlainPassword()}", 'password' => $wrapper->getPlainPassword()]);
        } else {
            Inertia::flash('toast', ['type' => 'success', 'message' => 'Usuario creado.']);
        }
    }

    /**
     * Flash the appropriate toast for password reset based on the password mode.
     */
    private function flashPasswordReset(UserWrapper $wrapper): void
    {
        if ($wrapper->sendsResetLink()) {
            Inertia::flash('toast', ['type' => 'success', 'message' => 'Link de contraseña enviado.']);
        } elseif ($wrapper->getPasswordMode() === 'random') {
            Inertia::flash('toast', ['type' => 'password', 'message' => "Contraseña cambiada. Nueva contraseña: {$wrapper->getPlainPassword()}", 'password' => $wrapper->getPlainPassword()]);
        } else {
            Inertia::flash('toast', ['type' => 'success', 'message' => 'Contraseña actualizada.']);
        }
    }
}
