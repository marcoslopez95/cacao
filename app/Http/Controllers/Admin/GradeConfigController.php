<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\CreateGradeConfigAction;
use App\Actions\Admin\UpdateGradeConfigAction;
use App\Enums\GradeLevel;
use App\Enums\GradeScaleType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGradeConfigRequest;
use App\Http\Requests\Admin\UpdateGradeConfigRequest;
use App\Http\Resources\Admin\GradeConfigResource;
use App\Http\Wrappers\Admin\GradeConfigWrapper;
use App\Models\GradeConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class GradeConfigController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', GradeConfig::class);

        $configs = GradeConfig::with(['slots', 'letterValues'])
            ->whereNull('period_id')
            ->orderBy('level')
            ->get();

        return Inertia::render('admin/GradeConfigs/Index', [
            'configs' => GradeConfigResource::collection($configs),
            'levels' => collect(GradeLevel::cases())->map(fn ($l) => ['value' => $l->value, 'label' => $l->label()]),
            'can' => ['create' => Gate::allows('create', GradeConfig::class)],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', GradeConfig::class);

        return Inertia::render('admin/GradeConfigs/Form', [
            'scaleTypes' => collect(GradeScaleType::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
            'levels' => collect(GradeLevel::cases())->map(fn ($l) => ['value' => $l->value, 'label' => $l->label()]),
        ]);
    }

    public function store(StoreGradeConfigRequest $request, CreateGradeConfigAction $action): RedirectResponse
    {
        $action->handle(new GradeConfigWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Configuración de notas creada.']);

        return to_route('security.grade-configs.index');
    }

    public function edit(GradeConfig $gradeConfig): Response
    {
        Gate::authorize('update', $gradeConfig);

        return Inertia::render('admin/GradeConfigs/Form', [
            'config' => new GradeConfigResource($gradeConfig->load(['slots', 'letterValues'])),
            'scaleTypes' => collect(GradeScaleType::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
            'levels' => collect(GradeLevel::cases())->map(fn ($l) => ['value' => $l->value, 'label' => $l->label()]),
        ]);
    }

    public function update(UpdateGradeConfigRequest $request, GradeConfig $gradeConfig, UpdateGradeConfigAction $action): RedirectResponse
    {
        $action->handle($gradeConfig, new GradeConfigWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Configuración de notas actualizada.']);

        return to_route('security.grade-configs.index');
    }
}
