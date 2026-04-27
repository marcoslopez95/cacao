<?php

namespace App\Http\Controllers\Infrastructure;

use App\Actions\Infrastructure\CreateClassroomAction;
use App\Actions\Infrastructure\DeleteClassroomAction;
use App\Actions\Infrastructure\UpdateClassroomAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Infrastructure\StoreClassroomRequest;
use App\Http\Requests\Infrastructure\UpdateClassroomRequest;
use App\Http\Resources\Infrastructure\BuildingResource;
use App\Http\Resources\Infrastructure\ClassroomResource;
use App\Http\Wrappers\Infrastructure\ClassroomWrapper;
use App\Models\Building;
use App\Models\Classroom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ClassroomController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Classroom::class);

        $classrooms = Classroom::with('building')
            ->when($request->integer('building_id') ?: null, fn ($q, $id) => $q->where('building_id', $id))
            ->orderBy('building_id')
            ->orderBy('identifier')
            ->get();

        $buildings = Building::orderBy('name')->get();

        return Inertia::render('infrastructure/Classrooms/Index', [
            'classrooms' => ClassroomResource::collection($classrooms)->resolve(),
            'buildings' => BuildingResource::collection($buildings)->resolve(),
            'filters' => [
                'buildingId' => $request->integer('building_id') ?: null,
            ],
            'can' => [
                'create' => $request->user()->can('create', Classroom::class),
                'update' => $request->user()->can('update', new Classroom),
                'delete' => $request->user()->can('delete', new Classroom),
            ],
        ]);
    }

    public function store(StoreClassroomRequest $request, CreateClassroomAction $action): RedirectResponse
    {
        $action->handle(new ClassroomWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Aula creada.']);

        return to_route('infrastructure.classrooms.index');
    }

    public function update(UpdateClassroomRequest $request, Classroom $classroom, UpdateClassroomAction $action): RedirectResponse
    {
        $action->handle($classroom, new ClassroomWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Aula actualizada.']);

        return to_route('infrastructure.classrooms.index');
    }

    public function destroy(Classroom $classroom, DeleteClassroomAction $action): RedirectResponse
    {
        Gate::authorize('delete', $classroom);

        $action->handle($classroom);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Aula eliminada.']);

        return to_route('infrastructure.classrooms.index');
    }
}
