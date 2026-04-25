<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\StoreCareerCategoryRequest;
use App\Http\Requests\Academic\UpdateCareerCategoryRequest;
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

        $categories = CareerCategory::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (CareerCategory $c) => [
                'id' => $c->id,
                'name' => $c->name,
            ]);

        $actor = $request->user();

        return Inertia::render('academic/CareerCategories/Index', [
            'categories' => $categories,
            'can' => [
                'create' => $actor->can('create', CareerCategory::class),
                'update' => $actor->can('update', new CareerCategory),
                'delete' => $actor->can('delete', new CareerCategory),
            ],
        ]);
    }

    public function store(StoreCareerCategoryRequest $request): RedirectResponse
    {
        CareerCategory::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Categoría creada.']);

        return to_route('academic.career-categories.index');
    }

    public function update(UpdateCareerCategoryRequest $request, CareerCategory $careerCategory): RedirectResponse
    {
        $careerCategory->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Categoría actualizada.']);

        return to_route('academic.career-categories.index');
    }

    public function destroy(Request $request, CareerCategory $careerCategory): RedirectResponse
    {
        Gate::authorize('delete', $careerCategory);

        if ($careerCategory->careers()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'No se puede eliminar: la categoría tiene carreras asociadas.',
            ]);

            return to_route('academic.career-categories.index');
        }

        $careerCategory->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Categoría eliminada.']);

        return to_route('academic.career-categories.index');
    }
}
