<?php

namespace App\Http\Controllers\Academic;

use App\Actions\Academic\CreateCareerAction;
use App\Actions\Academic\DeleteCareerAction;
use App\Actions\Academic\UpdateCareerAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\StoreCareerRequest;
use App\Http\Requests\Academic\UpdateCareerRequest;
use App\Http\Resources\Academic\CareerCategoryResource;
use App\Http\Resources\Academic\CareerResource;
use App\Http\Wrappers\Academic\CareerWrapper;
use App\Models\Career;
use App\Models\CareerCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CareerController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Career::class);

        $actor = $request->user();

        return Inertia::render('academic/Careers/Index', [
            'careers' => CareerResource::collection(
                Career::with('careerCategory')->withCount('pensums')->orderBy('name')->get()
            )->resolve(),
            'categories' => CareerCategoryResource::collection(
                CareerCategory::orderBy('name')->get()
            )->resolve(),
            'can' => [
                'create' => $actor->can('create', Career::class),
                'update' => $actor->can('update', new Career),
                'delete' => $actor->can('delete', new Career),
            ],
        ]);
    }

    public function store(StoreCareerRequest $request, CreateCareerAction $action): RedirectResponse
    {
        $action->handle(new CareerWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Carrera creada.']);

        return to_route('academic.careers.index');
    }

    public function update(UpdateCareerRequest $request, Career $career, UpdateCareerAction $action): RedirectResponse
    {
        $action->handle($career, new CareerWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Carrera actualizada.']);

        return to_route('academic.careers.index');
    }

    public function destroy(Career $career, DeleteCareerAction $action): RedirectResponse
    {
        Gate::authorize('delete', $career);

        if (! $action->handle($career)) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'No se puede eliminar: la carrera tiene pensums asociados.',
            ]);

            return to_route('academic.careers.index');
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Carrera eliminada.']);

        return to_route('academic.careers.index');
    }
}
