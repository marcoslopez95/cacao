<?php

namespace App\Http\Controllers\Academic;

use App\Actions\Academic\CreatePensumAction;
use App\Actions\Academic\DeletePensumAction;
use App\Actions\Academic\UpdatePensumAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\StorePensumRequest;
use App\Http\Requests\Academic\UpdatePensumRequest;
use App\Http\Resources\Academic\CareerResource;
use App\Http\Resources\Academic\PensumResource;
use App\Http\Wrappers\Academic\PensumWrapper;
use App\Models\Career;
use App\Models\Pensum;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PensumController extends Controller
{
    public function index(Request $request, Career $career): Response
    {
        Gate::authorize('viewAny', Pensum::class);

        $actor = $request->user();

        return Inertia::render('academic/Pensums/Index', [
            'career' => (new CareerResource($career->load('careerCategory')))->resolve(),
            'pensums' => PensumResource::collection(
                $career->pensums()->withCount('subjects')->orderBy('name')->get()
            )->resolve(),
            'can' => [
                'create' => $actor->can('create', Pensum::class),
                'update' => $actor->can('update', new Pensum),
                'delete' => $actor->can('delete', new Pensum),
            ],
        ]);
    }

    public function store(StorePensumRequest $request, Career $career, CreatePensumAction $action): RedirectResponse
    {
        $action->handle(new PensumWrapper($request->validated() + ['career_id' => $career->id]));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pensum creado.']);

        return to_route('academic.pensums.index', $career);
    }

    public function update(UpdatePensumRequest $request, Career $career, Pensum $pensum, UpdatePensumAction $action): RedirectResponse
    {
        $action->handle($pensum, new PensumWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pensum actualizado.']);

        return to_route('academic.pensums.index', $career);
    }

    public function destroy(Career $career, Pensum $pensum, DeletePensumAction $action): RedirectResponse
    {
        Gate::authorize('delete', $pensum);

        if (! $action->handle($pensum)) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'No se puede eliminar: el pensum tiene materias asociadas.',
            ]);

            return to_route('academic.pensums.index', $career);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pensum eliminado.']);

        return to_route('academic.pensums.index', $career);
    }
}
