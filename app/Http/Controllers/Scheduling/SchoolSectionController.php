<?php

namespace App\Http\Controllers\Scheduling;

use App\Actions\Scheduling\CreateSchoolSectionAction;
use App\Actions\Scheduling\DeleteSectionAction;
use App\Actions\Scheduling\UpdateSchoolSectionAction;
use App\Enums\PeriodType;
use App\Enums\SectionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Scheduling\StoreSchoolSectionRequest;
use App\Http\Requests\Scheduling\UpdateSchoolSectionRequest;
use App\Http\Resources\Scheduling\SectionResource;
use App\Http\Wrappers\Scheduling\SchoolSectionWrapper;
use App\Models\Classroom;
use App\Models\Pensum;
use App\Models\Period;
use App\Models\Professor;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SchoolSectionController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Section::class);

        $sections = Section::where('type', SectionType::School)
            ->when($request->input('period_id'), fn ($q, $id) => $q->where('period_id', $id))
            ->when($request->input('pensum_id'), fn ($q, $id) => $q->where('pensum_id', $id))
            ->with(['period', 'pensum.career', 'mainTeacher.user', 'classroom', 'sectionSubjects'])
            ->orderByDesc('id')
            ->get();

        $periods = Period::where('type', PeriodType::Year)
            ->orderByDesc('id')
            ->get(['id', 'name', 'type']);

        $pensums = Pensum::with('career:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'career_id', 'total_periods']);

        $professors = Professor::with('user:id,name')
            ->where('active', true)
            ->orderBy('id')
            ->get(['id', 'user_id']);

        $classrooms = Classroom::orderBy('identifier')
            ->get(['id', 'identifier', 'capacity']);

        return Inertia::render('scheduling/Sections/School', [
            'sections'   => SectionResource::collection($sections)->resolve(),
            'periods'    => $periods->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'type' => $p->type->value]),
            'pensums'    => $pensums->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'totalPeriods' => $p->total_periods, 'career' => ['id' => $p->career->id, 'name' => $p->career->name]]),
            'professors' => $professors->map(fn ($p) => ['id' => $p->id, 'user' => ['id' => $p->user->id, 'name' => $p->user->name]]),
            'classrooms' => $classrooms->map(fn ($c) => ['id' => $c->id, 'identifier' => $c->identifier, 'capacity' => $c->capacity]),
            'filters'    => [
                'period_id' => $request->input('period_id') ? (int) $request->input('period_id') : null,
                'pensum_id' => $request->input('pensum_id') ? (int) $request->input('pensum_id') : null,
            ],
            'can'        => [
                'create' => $request->user()->can('sections.create'),
                'update' => $request->user()->can('sections.update'),
                'delete' => $request->user()->can('sections.delete'),
            ],
        ]);
    }

    public function store(StoreSchoolSectionRequest $request, CreateSchoolSectionAction $action): RedirectResponse
    {
        $action->handle(new SchoolSectionWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sección escolar creada.']);

        return to_route('scheduling.sections.school.index');
    }

    public function update(UpdateSchoolSectionRequest $request, Section $section, UpdateSchoolSectionAction $action): RedirectResponse
    {
        $action->handle($section, new SchoolSectionWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sección escolar actualizada.']);

        return to_route('scheduling.sections.school.index');
    }

    public function destroy(Section $section, DeleteSectionAction $action): RedirectResponse
    {
        Gate::authorize('delete', $section);

        $action->handle($section);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sección escolar eliminada.']);

        return to_route('scheduling.sections.school.index');
    }
}
