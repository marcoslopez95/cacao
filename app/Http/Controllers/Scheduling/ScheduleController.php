<?php

namespace App\Http\Controllers\Scheduling;

use App\Actions\Scheduling\CreateScheduleAction;
use App\Actions\Scheduling\DeleteScheduleAction;
use App\Actions\Scheduling\UpdateScheduleAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Scheduling\StoreScheduleRequest;
use App\Http\Requests\Scheduling\UpdateScheduleRequest;
use App\Http\Resources\Scheduling\ScheduleResource;
use App\Http\Wrappers\Scheduling\ScheduleWrapper;
use App\Models\Career;
use App\Models\Classroom;
use App\Models\Period;
use App\Models\Professor;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Schedule::class);

        $careerIds = $request->input('career_ids') ? array_map('intval', (array) $request->input('career_ids')) : null;

        $schedules = Schedule::with(['section', 'professor.user', 'classroom', 'subject.pensum.career'])
            ->when($request->input('section_id'), fn ($q, $id) => $q->where('section_id', $id))
            ->when($request->input('professor_id'), fn ($q, $id) => $q->where('professor_id', $id))
            ->when($request->input('period_id'), fn ($q, $id) => $q->whereHas('section', fn ($q2) => $q2->where('period_id', $id)))
            ->when($request->input('day_of_week'), fn ($q, $day) => $q->where('day_of_week', $day))
            ->when($careerIds, fn ($q, $ids) => $q->whereHas('subject.pensum.career', fn ($q2) => $q2->whereIn('id', $ids)))
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        $periods = Period::orderByDesc('id')->get(['id', 'name']);
        $sections = Section::with(['period:id,name', 'subject.pensum:id,career_id'])->orderByDesc('id')->get(['id', 'code', 'type', 'period_id', 'subject_id']);
        $professors = Professor::with('user:id,name')->where('active', true)->orderBy('id')->get(['id', 'user_id', 'weekly_hour_limit']);
        $classrooms = Classroom::orderBy('identifier')->get(['id', 'identifier']);
        $subjects = Subject::orderBy('name')->get(['id', 'name', 'code']);

        $professorHours = Schedule::selectRaw('professor_id, SUM(EXTRACT(EPOCH FROM (end_time - start_time)) / 3600) as total_hours')
            ->groupBy('professor_id')
            ->pluck('total_hours', 'professor_id');

        $careers = Career::whereHas('pensums.subjects.sections.schedules', function ($q) use ($request) {
            $q->when($request->input('section_id'), fn ($q2, $id) => $q2->where('section_id', $id))
                ->when($request->input('professor_id'), fn ($q2, $id) => $q2->where('professor_id', $id))
                ->when($request->input('period_id'), fn ($q2, $id) => $q2->whereHas('section', fn ($q3) => $q3->where('period_id', $id)));
        })->orderBy('name')->get(['id', 'name']);

        return Inertia::render('scheduling/Schedules/Index', [
            'schedules' => ScheduleResource::collection($schedules)->resolve(),
            'periods' => $periods->map(fn ($p) => ['id' => $p->id, 'name' => $p->name]),
            'sections' => $sections->map(fn ($s) => ['id' => $s->id, 'code' => $s->code, 'type' => $s->type->value, 'periodId' => $s->period_id, 'periodName' => $s->period?->name, 'careerId' => $s->subject?->pensum?->career_id]),
            'professors' => $professors->map(fn ($p) => ['id' => $p->id, 'name' => $p->user->name, 'weeklyHourLimit' => $p->weekly_hour_limit, 'currentWeeklyHours' => round($professorHours->get($p->id, 0), 1)]),
            'classrooms' => $classrooms->map(fn ($c) => ['id' => $c->id, 'identifier' => $c->identifier]),
            'subjects' => $subjects->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'code' => $s->code]),
            'careers' => $careers->map(fn ($c) => ['id' => $c->id, 'name' => $c->name]),
            'filters' => [
                'period_id' => $request->input('period_id') ? (int) $request->input('period_id') : null,
                'section_id' => $request->input('section_id') ? (int) $request->input('section_id') : null,
                'professor_id' => $request->input('professor_id') ? (int) $request->input('professor_id') : null,
                'career_ids' => $careerIds,
            ],
            'can' => [
                'create' => $request->user()->can('schedules.create'),
                'update' => $request->user()->can('schedules.update'),
                'delete' => $request->user()->can('schedules.delete'),
            ],
        ]);
    }

    public function store(StoreScheduleRequest $request, CreateScheduleAction $action): RedirectResponse
    {
        $action->handle(new ScheduleWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Horario creado.']);

        return to_route('scheduling.schedules.index', $this->redirectFilters($request));
    }

    public function update(UpdateScheduleRequest $request, Schedule $schedule, UpdateScheduleAction $action): RedirectResponse
    {
        $action->handle($schedule, new ScheduleWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Horario actualizado.']);

        return to_route('scheduling.schedules.index', $this->redirectFilters($request));
    }

    public function destroy(Request $request, Schedule $schedule, DeleteScheduleAction $action): RedirectResponse
    {
        Gate::authorize('delete', $schedule);

        $action->handle($schedule);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Horario eliminado.']);

        return to_route('scheduling.schedules.index', $this->redirectFilters($request));
    }

    private function redirectFilters(Request $request): array
    {
        $periodId = $request->input('period_id')
            ?? Section::find($request->input('section_id'))?->period_id;

        return array_filter([
            'section_id' => $request->input('section_id'),
            'period_id' => $periodId,
            'professor_id' => $request->input('professor_id'),
        ]);
    }
}
