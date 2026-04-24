<?php

namespace App\Http\Controllers\Security;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Security\StoreCoordinationRequest;
use App\Http\Requests\Security\UpdateCoordinationRequest;
use App\Models\Coordination;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CoordinationController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Coordination::class);

        $actor = $request->user();

        $query = Coordination::query()->with(['currentAssignment.user']);

        if ($search = $request->input('search')) {
            $query->where('name', 'ilike', "%{$search}%");
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($level = $request->input('education_level')) {
            $query->where('education_level', $level);
        }

        if ($status = $request->input('status')) {
            match ($status) {
                'active' => $query->where('active', true),
                'inactive' => $query->where('active', false),
                default => null,
            };
        }

        $perPage = min(100, max(10, (int) $request->input('per_page', 20)));

        $coordinations = $query
            ->orderBy('name')
            ->paginate($perPage)
            ->through(fn (Coordination $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'type' => $c->type,
                'education_level' => $c->education_level,
                'secondary_type' => $c->secondary_type,
                'career_id' => $c->career_id,
                'grade_year' => $c->grade_year,
                'active' => $c->active,
                'current_coordinator' => $c->currentAssignment?->user
                    ? ['id' => $c->currentAssignment->user->id, 'name' => $c->currentAssignment->user->name]
                    : null,
            ]);

        $coordinators = User::role(Role::Coordinator->value)
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name])
            ->values();

        return Inertia::render('security/Coordinations/Index', [
            'coordinations' => $coordinations,
            'coordinators' => $coordinators,
            'careers' => [], // populated when Academic module is built
            'filters' => $request->only('search', 'type', 'education_level', 'status'),
            'can' => [
                'create' => $actor->can('create', Coordination::class),
                'update' => $actor->can('update', new Coordination),
                'delete' => $actor->can('delete', new Coordination),
                'assign' => $actor->can('assign', new Coordination),
                'viewHistory' => $actor->can('viewHistory', new Coordination),
            ],
        ]);
    }

    public function store(StoreCoordinationRequest $request): RedirectResponse
    {
        Coordination::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Coordinación creada.']);

        return to_route('security.coordinations.index');
    }

    public function update(UpdateCoordinationRequest $request, Coordination $coordination): RedirectResponse
    {
        $coordination->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Coordinación actualizada.']);

        return to_route('security.coordinations.index');
    }

    public function destroy(Request $request, Coordination $coordination): RedirectResponse
    {
        Gate::authorize('delete', $coordination);

        if ($coordination->currentAssignment()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'No se puede eliminar: la coordinación tiene un coordinador activo.',
            ]);

            return to_route('security.coordinations.index');
        }

        $coordination->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Coordinación eliminada.']);

        return to_route('security.coordinations.index');
    }
}
