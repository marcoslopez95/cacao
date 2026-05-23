<?php

namespace App\Http\Controllers\Professor;

use App\Actions\Admin\EnableRemedialAction;
use App\Http\Controllers\Controller;
use App\Models\EnrollmentDetail;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class RemedialController extends Controller
{
    public function store(Request $request, Section $section, EnrollmentDetail $detail, EnableRemedialAction $action): RedirectResponse
    {
        Gate::authorize('upsert', [Section::class, $section]);

        abort_unless($detail->section_id === $section->id, 403);

        $team = $request->user()->currentTeam;
        $entry = $action->handle($detail->load('enrollment.student'), $team);

        abort_if($entry === null, 422, 'No hay slot de reparación configurado para este nivel educativo.');

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Reparación habilitada.']);

        return back();
    }
}
