<?php

namespace App\Http\Controllers\Scheduling;

use App\Actions\Scheduling\CreateUniversitySectionAction;
use App\Actions\Scheduling\DeleteSectionAction;
use App\Actions\Scheduling\UpdateUniversitySectionAction;
use App\Enums\ClassroomType;
use App\Enums\PeriodType;
use App\Enums\SectionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Scheduling\StoreUniversitySectionRequest;
use App\Http\Requests\Scheduling\UpdateUniversitySectionRequest;
use App\Http\Resources\Scheduling\SectionResource;
use App\Http\Wrappers\Scheduling\UniversitySectionWrapper;
use App\Models\Classroom;
use App\Models\Period;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class UniversitySectionController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Section::class);

        $sections = Section::where('type', SectionType::University)
            ->when($request->input('period_id'), fn ($q, $id) => $q->where('period_id', $id))
            ->when($request->input('subject'), fn ($q, $s) => $q->whereHas('subject', fn ($q2) => $q2->where('name', 'ilike', "%{$s}%")->orWhere('code', 'ilike', "%{$s}%")))
            ->with(['period', 'subject', 'theoryClassroom', 'labClassroom'])
            ->orderByDesc('id')
            ->get();

        $periods = Period::whereIn('type', [PeriodType::Semester, PeriodType::Trimester])
            ->orderByDesc('id')
            ->get(['id', 'name', 'type']);

        $subjects = Subject::with('pensum:id,period_type')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'pensum_id']);

        $classrooms = Classroom::orderBy('identifier')->get(['id', 'identifier', 'type', 'capacity']);

        return Inertia::render('scheduling/Sections/University', [
            'sections'   => SectionResource::collection($sections)->resolve(),
            'periods'    => $periods->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'type' => $p->type->value]),
            'subjects'   => $subjects->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'code' => $s->code, 'pensumPeriodType' => $s->pensum?->period_type]),
            'classrooms' => $classrooms->map(fn ($c) => ['id' => $c->id, 'identifier' => $c->identifier, 'type' => $c->type->value, 'capacity' => $c->capacity]),
            'filters'    => ['period_id' => $request->input('period_id') ? (int) $request->input('period_id') : null, 'subject' => $request->input('subject')],
            'can'        => [
                'create' => $request->user()->can('sections.create'),
                'update' => $request->user()->can('sections.update'),
                'delete' => $request->user()->can('sections.delete'),
            ],
        ]);
    }

    public function store(StoreUniversitySectionRequest $request, CreateUniversitySectionAction $action): RedirectResponse
    {
        $action->handle(new UniversitySectionWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sección creada.']);

        return to_route('scheduling.sections.university.index');
    }

    public function update(UpdateUniversitySectionRequest $request, Section $section, UpdateUniversitySectionAction $action): RedirectResponse
    {
        $action->handle($section, new UniversitySectionWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sección actualizada.']);

        return to_route('scheduling.sections.university.index');
    }

    public function destroy(Section $section, DeleteSectionAction $action): RedirectResponse
    {
        Gate::authorize('delete', $section);

        $action->handle($section);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sección eliminada.']);

        return to_route('scheduling.sections.university.index');
    }
}
