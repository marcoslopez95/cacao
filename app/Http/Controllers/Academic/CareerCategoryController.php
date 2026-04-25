<?php

namespace App\Http\Controllers\Academic;

use App\Actions\Academic\CreateCareerCategoryAction;
use App\Actions\Academic\DeleteCareerCategoryAction;
use App\Actions\Academic\UpdateCareerCategoryAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\StoreCareerCategoryRequest;
use App\Http\Requests\Academic\UpdateCareerCategoryRequest;
use App\Http\Resources\Academic\CareerCategoryResource;
use App\Http\Wrappers\Academic\CareerCategoryWrapper;
use App\Models\CareerCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CareerCategoryController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', CareerCategory::class);

        $actor = $request->user();

        return Inertia::render('academic/CareerCategories/Index', [
            'categories' => CareerCategoryResource::collection(
                CareerCategory::orderBy('name')->get()
            )->resolve(),
            'can' => [
                'create' => $actor->can('create', CareerCategory::class),
                'update' => $actor->can('update', new CareerCategory),
                'delete' => $actor->can('delete', new CareerCategory),
            ],
        ]);
    }

    public function store(StoreCareerCategoryRequest $request, CreateCareerCategoryAction $action): RedirectResponse
    {
        $action->handle(new CareerCategoryWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Categoría creada.']);

        return to_route('academic.career-categories.index');
    }

    public function update(UpdateCareerCategoryRequest $request, CareerCategory $careerCategory, UpdateCareerCategoryAction $action): RedirectResponse
    {
        $action->handle($careerCategory, new CareerCategoryWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Categoría actualizada.']);

        return to_route('academic.career-categories.index');
    }

    public function destroy(CareerCategory $careerCategory, DeleteCareerCategoryAction $action): RedirectResponse
    {
        Gate::authorize('delete', $careerCategory);

        if (! $action->handle($careerCategory)) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'No se puede eliminar: la categoría tiene carreras asociadas.',
            ]);

            return to_route('academic.career-categories.index');
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Categoría eliminada.']);

        return to_route('academic.career-categories.index');
    }
}
