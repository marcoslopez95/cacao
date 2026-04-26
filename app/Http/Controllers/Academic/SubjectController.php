<?php

namespace App\Http\Controllers\Academic;

use App\Actions\Academic\CreateSubjectAction;
use App\Actions\Academic\DeleteSubjectAction;
use App\Actions\Academic\SyncPrerequisitesAction;
use App\Actions\Academic\UpdateSubjectAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\StoreSubjectRequest;
use App\Http\Requests\Academic\SyncPrerequisitesRequest;
use App\Http\Requests\Academic\UpdateSubjectRequest;
use App\Http\Resources\Academic\CareerResource;
use App\Http\Resources\Academic\PensumResource;
use App\Http\Resources\Academic\SubjectResource;
use App\Http\Wrappers\Academic\SubjectWrapper;
use App\Models\Career;
use App\Models\Pensum;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SubjectController extends Controller
{
    public function index(Request $request, Career $career, Pensum $pensum): Response
    {
        Gate::authorize('viewAny', Subject::class);

        $subjects = $pensum->subjects()->with('prerequisites')->get();

        $actor = $request->user();

        return Inertia::render('academic/Subjects/Index', [
            'career' => (new CareerResource($career->load('careerCategory')))->resolve(),
            'pensum' => (new PensumResource($pensum))->resolve(),
            'subjects' => SubjectResource::collection($subjects)->resolve(),
            'can' => [
                'create' => $actor->can('create', Subject::class),
                'update' => $actor->can('update', new Subject),
                'delete' => $actor->can('delete', new Subject),
                'managePrerequisites' => $actor->can('managePrerequisites', new Subject),
            ],
        ]);
    }

    public function store(StoreSubjectRequest $request, Career $career, Pensum $pensum, CreateSubjectAction $action): RedirectResponse
    {
        $action->handle(new SubjectWrapper($request->validated() + ['pensum_id' => $pensum->id]), $career);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Materia creada.']);

        return to_route('academic.subjects.index', ['career' => $career, 'pensum' => $pensum]);
    }

    public function update(UpdateSubjectRequest $request, Career $career, Pensum $pensum, Subject $subject, UpdateSubjectAction $action): RedirectResponse
    {
        $action->handle($subject, new SubjectWrapper($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Materia actualizada.']);

        return to_route('academic.subjects.index', ['career' => $career, 'pensum' => $pensum]);
    }

    public function destroy(Career $career, Pensum $pensum, Subject $subject, DeleteSubjectAction $action): RedirectResponse
    {
        Gate::authorize('delete', $subject);

        if (! $action->handle($subject)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'No se puede eliminar: otra materia tiene esta como prerrequisito.']);
        } else {
            Inertia::flash('toast', ['type' => 'success', 'message' => 'Materia eliminada.']);
        }

        return to_route('academic.subjects.index', ['career' => $career, 'pensum' => $pensum]);
    }

    public function syncPrerequisites(SyncPrerequisitesRequest $request, Career $career, Pensum $pensum, Subject $subject, SyncPrerequisitesAction $action): RedirectResponse
    {
        $action->handle($subject, $request->validated('prerequisites'));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Prerrequisitos actualizados.']);

        return to_route('academic.subjects.index', ['career' => $career, 'pensum' => $pensum]);
    }
}
