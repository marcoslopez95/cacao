<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Http\Requests\Security\ResetPasswordRequest;
use App\Http\Requests\Security\StoreUserRequest;
use App\Http\Requests\Security\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
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

        if ($search = $request->input('search')) {
            $query->where(fn ($q) => $q
                ->where('name', 'ilike', "%{$search}%")
                ->orWhere('email', 'ilike', "%{$search}%")
            );
        }

        if ($role = $request->input('role')) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $role));
        }

        if ($status = $request->input('status')) {
            match ($status) {
                'active' => $query->where('active', true),
                'inactive' => $query->where('active', false),
                default => null,
            };
        }

        $perPage = min(100, max(10, (int) $request->input('per_page', 20)));

        $users = $query->orderBy('name')->paginate($perPage)->through(fn (User $user) => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'active' => $user->active,
            'roles' => $user->roles->pluck('name')->values(),
            'created_at' => $user->created_at?->toDateString(),
        ]);

        return Inertia::render('security/Users/Index', [
            'users' => $users,
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
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $password = match ($data['password_mode']) {
            'manual' => $data['password'],
            'random' => Str::random(16),
            default => null,
        };

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($password ?? Str::random(32)),
            'active' => true,
        ]);

        $user->syncRoles([$data['role']]);

        if ($data['password_mode'] === 'link') {
            Password::sendResetLink(['email' => $user->email]);
            Inertia::flash('toast', ['type' => 'success', 'message' => 'Usuario creado. Se envió un link para establecer la contraseña.']);
        } elseif ($data['password_mode'] === 'random') {
            Inertia::flash('toast', [
                'type' => 'password',
                'message' => "Usuario creado. Contraseña generada: {$password}",
                'password' => $password,
            ]);
        } else {
            Inertia::flash('toast', ['type' => 'success', 'message' => 'Usuario creado.']);
        }

        return to_route('security.users.index');
    }

    /**
     * Update the specified user's name, email and roles.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        $user->syncRoles($data['roles'] ?? []);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Usuario actualizado.']);

        return to_route('security.users.index');
    }

    /**
     * Delete the specified user.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('delete', $user);

        $user->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Usuario eliminado.']);

        return to_route('security.users.index');
    }

    /**
     * Toggle the active state of the specified user.
     */
    public function deactivate(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('deactivate', $user);

        $isNowActive = ! $user->active;
        $user->update(['active' => $isNowActive]);

        $msg = $isNowActive ? 'Usuario reactivado.' : 'Usuario desactivado.';
        Inertia::flash('toast', ['type' => 'success', 'message' => $msg]);

        return to_route('security.users.index');
    }

    /**
     * Reset the specified user's password.
     */
    public function resetPassword(ResetPasswordRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        $password = match ($data['password_mode']) {
            'manual' => $data['password'],
            'random' => Str::random(16),
            default => null,
        };

        if ($data['password_mode'] === 'link') {
            Password::sendResetLink(['email' => $user->email]);
            Inertia::flash('toast', ['type' => 'success', 'message' => 'Link de contraseña enviado.']);
        } else {
            $user->update(['password' => Hash::make($password)]);

            if ($data['password_mode'] === 'random') {
                Inertia::flash('toast', [
                    'type' => 'password',
                    'message' => "Contraseña cambiada. Nueva contraseña: {$password}",
                    'password' => $password,
                ]);
            } else {
                Inertia::flash('toast', ['type' => 'success', 'message' => 'Contraseña actualizada.']);
            }
        }

        return to_route('security.users.index');
    }
}
