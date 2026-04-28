<?php

namespace App\Http\Controllers\Scheduling;

use App\Actions\Scheduling\ActivatePeriodAction;
use App\Actions\Scheduling\ClosePeriodAction;
use App\Actions\Scheduling\CreatePeriodAction;
use App\Actions\Scheduling\DeletePeriodAction;
use App\Actions\Scheduling\UpdatePeriodAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Scheduling\StorePeriodRequest;
use App\Http\Requests\Scheduling\UpdatePeriodRequest;
use App\Http\Resources\Scheduling\PeriodResource;
use App\Http\Wrappers\Scheduling\PeriodWrapper;
use App\Models\Period;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PeriodController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Period::class);

        return Inertia::render('scheduling/Periods/Index', [
            'periods' => PeriodResource::collection(Period::when($request->input('type'), fn ($q, $t) => $q->where('type', $t))->orderByDesc('id')->get())->resolve(),
            'filters' => ['type' => $request->input('type')],
            'can'     => ['create' => $request->user()->can('create', Period::class), 'update' => $request->user()->can('periods.update'), 'delete' => $request->user()->can('periods.delete')],
        ]);
    }

    public function store(StorePeriodRequest $request, CreatePeriodAction $action): RedirectResponse
    {
        $action->handle(new PeriodWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Período creado.']);

        return to_route('scheduling.periods.index');
    }

    public function update(UpdatePeriodRequest $request, Period $period, UpdatePeriodAction $action): RedirectResponse
    {
        $action->handle($period, new PeriodWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Período actualizado.']);

        return to_route('scheduling.periods.index');
    }

    public function activate(Period $period, ActivatePeriodAction $action): RedirectResponse
    {
        Gate::authorize('update', $period);

        if (! $action->handle($period)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'No se puede activar el período desde su estado actual.']);
        } else {
            Inertia::flash('toast', ['type' => 'success', 'message' => 'Período activado.']);
        }

        return to_route('scheduling.periods.index');
    }

    public function close(Period $period, ClosePeriodAction $action): RedirectResponse
    {
        Gate::authorize('update', $period);

        if (! $action->handle($period)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'No se puede cerrar el período desde su estado actual.']);
        } else {
            Inertia::flash('toast', ['type' => 'success', 'message' => 'Período cerrado.']);
        }

        return to_route('scheduling.periods.index');
    }

    public function destroy(Period $period, DeletePeriodAction $action): RedirectResponse
    {
        Gate::authorize('delete', $period);

        if (! $action->handle($period)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Solo se pueden eliminar períodos en estado Próximo.']);
        } else {
            Inertia::flash('toast', ['type' => 'success', 'message' => 'Período eliminado.']);
        }

        return to_route('scheduling.periods.index');
    }
}
