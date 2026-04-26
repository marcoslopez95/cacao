<?php

namespace App\Http\Controllers\Security;

use App\Actions\Security\CreateCoordinationAction;
use App\Actions\Security\DeleteCoordinationAction;
use App\Actions\Security\UpdateCoordinationAction;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Security\StoreCoordinationRequest;
use App\Http\Requests\Security\UpdateCoordinationRequest;
use App\Http\Resources\Security\CoordinationResource;
use App\Http\Wrappers\Security\CoordinationWrapper;
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

        $query->when($request->input('search'), fn ($q, $s) => $q->where('name', 'ilike', "%{$s}%"));
        $query->when($request->input('type'), fn ($q, $t) => $q->where('type', $t));
        $query->when($request->input('education_level'), fn ($q, $l) => $q->where('education_level', $l));
        $query->when($request->input('status'), function ($q, $s): void {
            match ($s) {
                'active' => $q->where('active', true),
                'inactive' => $q->where('active', false),
                default => null,
            };
        });

        $perPage = min(100, max(10, (int) $request->input('per_page', 20)));

        $coordinators = User::role(Role::Coordinator->value)
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name])
            ->values();

        return Inertia::render('security/Coordinations/Index', [
            'coordinations' => CoordinationResource::collection($query->orderBy('name')->paginate($perPage)),
            'coordinators' => $coordinators,
            'careers' => [],
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

    public function store(StoreCoordinationRequest $request, CreateCoordinationAction $action): RedirectResponse
    {
        $action->handle(new CoordinationWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Coordinación creada.']);

        return to_route('security.coordinations.index');
    }

    public function update(UpdateCoordinationRequest $request, Coordination $coordination, UpdateCoordinationAction $action): RedirectResponse
    {
        $action->handle($coordination, new CoordinationWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Coordinación actualizada.']);

        return to_route('security.coordinations.index');
    }

    public function destroy(Request $request, Coordination $coordination, DeleteCoordinationAction $action): RedirectResponse
    {
        Gate::authorize('delete', $coordination);

        if (! $action->handle($coordination)) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'No se puede eliminar: la coordinación tiene asignaciones registradas.',
            ]);

            return to_route('security.coordinations.index');
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Coordinación eliminada.']);

        return to_route('security.coordinations.index');
    }
}
