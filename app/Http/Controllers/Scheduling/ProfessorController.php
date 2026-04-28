<?php

namespace App\Http\Controllers\Scheduling;

use App\Actions\Scheduling\CreateProfessorAction;
use App\Actions\Scheduling\DeleteProfessorAction;
use App\Actions\Scheduling\UpdateProfessorAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Scheduling\StoreProfessorRequest;
use App\Http\Requests\Scheduling\UpdateProfessorRequest;
use App\Http\Resources\Scheduling\ProfessorResource;
use App\Http\Wrappers\Scheduling\ProfessorWrapper;
use App\Models\Professor;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class ProfessorController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Professor::class);

        $professors = Professor::with('user')->orderByDesc('id')->get();

        $existingUserIds = $professors->pluck('user_id');

        $profesorRole = Role::findByName('Profesor', 'web');
        $availableUsers = $profesorRole
            ? $profesorRole->users()->whereNotIn('users.id', $existingUserIds)->get(['users.id', 'users.name', 'users.email'])
            : collect();

        return Inertia::render('scheduling/Professors/Index', [
            'professors'     => ProfessorResource::collection($professors)->resolve(),
            'availableUsers' => $availableUsers->values(),
            'can'            => [
                'create' => $request->user()->can('professors.create'),
                'update' => $request->user()->can('professors.update'),
                'delete' => $request->user()->can('professors.delete'),
            ],
        ]);
    }

    public function store(StoreProfessorRequest $request, CreateProfessorAction $action): RedirectResponse
    {
        $action->handle(new ProfessorWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Profesor creado.']);

        return to_route('scheduling.professors.index');
    }

    public function update(UpdateProfessorRequest $request, Professor $professor, UpdateProfessorAction $action): RedirectResponse
    {
        $action->handle($professor, new ProfessorWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Profesor actualizado.']);

        return to_route('scheduling.professors.index');
    }

    public function destroy(Professor $professor, DeleteProfessorAction $action): RedirectResponse
    {
        Gate::authorize('delete', $professor);

        $action->handle($professor);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Profesor eliminado.']);

        return to_route('scheduling.professors.index');
    }
}
