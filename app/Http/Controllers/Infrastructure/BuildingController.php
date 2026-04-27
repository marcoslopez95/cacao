<?php

namespace App\Http\Controllers\Infrastructure;

use App\Actions\Infrastructure\CreateBuildingAction;
use App\Actions\Infrastructure\DeleteBuildingAction;
use App\Actions\Infrastructure\UpdateBuildingAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Infrastructure\StoreBuildingRequest;
use App\Http\Requests\Infrastructure\UpdateBuildingRequest;
use App\Http\Resources\Infrastructure\BuildingResource;
use App\Http\Wrappers\Infrastructure\BuildingWrapper;
use App\Models\Building;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BuildingController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Building::class);

        $buildings = Building::withCount('classrooms')->orderBy('name')->get();

        return Inertia::render('infrastructure/Buildings/Index', [
            'buildings' => BuildingResource::collection($buildings)->resolve(),
            'can' => [
                'create' => $request->user()->can('create', Building::class),
                'update' => $request->user()->can('update', new Building),
                'delete' => $request->user()->can('delete', new Building),
            ],
        ]);
    }

    public function store(StoreBuildingRequest $request, CreateBuildingAction $action): RedirectResponse
    {
        $action->handle(new BuildingWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Edificio creado.']);

        return to_route('infrastructure.buildings.index');
    }

    public function update(UpdateBuildingRequest $request, Building $building, UpdateBuildingAction $action): RedirectResponse
    {
        $action->handle($building, new BuildingWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Edificio actualizado.']);

        return to_route('infrastructure.buildings.index');
    }

    public function destroy(Building $building, DeleteBuildingAction $action): RedirectResponse
    {
        Gate::authorize('delete', $building);

        if (! $action->handle($building)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'No se puede eliminar: el edificio tiene aulas asignadas.']);
        } else {
            Inertia::flash('toast', ['type' => 'success', 'message' => 'Edificio eliminado.']);
        }

        return to_route('infrastructure.buildings.index');
    }
}
