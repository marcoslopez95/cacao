<?php

namespace App\Http\Controllers\Scheduling;

use App\Actions\Scheduling\CreateLapseAction;
use App\Actions\Scheduling\DeleteLapseAction;
use App\Actions\Scheduling\UpdateLapseAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Scheduling\StoreLapseRequest;
use App\Http\Requests\Scheduling\UpdateLapseRequest;
use App\Http\Wrappers\Scheduling\LapseWrapper;
use App\Models\Lapse;
use App\Models\Period;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class LapseController extends Controller
{
    public function store(StoreLapseRequest $request, Period $period, CreateLapseAction $action): RedirectResponse
    {
        $action->handle($period, new LapseWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Lapso creado.']);

        return to_route('scheduling.periods.index');
    }

    public function update(UpdateLapseRequest $request, Period $period, Lapse $lapse, UpdateLapseAction $action): RedirectResponse
    {
        $action->handle($lapse, new LapseWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Lapso actualizado.']);

        return to_route('scheduling.periods.index');
    }

    public function destroy(Period $period, Lapse $lapse, DeleteLapseAction $action): RedirectResponse
    {
        Gate::authorize('delete', $lapse);

        $action->handle($lapse);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Lapso eliminado.']);

        return to_route('scheduling.periods.index');
    }
}
