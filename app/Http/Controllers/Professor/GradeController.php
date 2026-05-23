<?php

namespace App\Http\Controllers\Professor;

use App\Actions\Professor\PublishGradeSlotAction;
use App\Actions\Professor\UpsertGradeEntryAction;
use App\Enums\PeriodStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Professor\UpsertGradeEntryRequest;
use App\Http\Resources\Professor\SectionGradeSheetResource;
use App\Http\Wrappers\Professor\GradeEntryWrapper;
use App\Models\GradeConfig;
use App\Models\GradeSlot;
use App\Models\Period;
use App\Models\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class GradeController extends Controller
{
    public function sheet(Request $request, Section $section): Response
    {
        Gate::authorize('upsert', [Section::class, $section]);

        $section->load(['subject', 'enrollmentDetails.enrollment.student.user']);

        $period = Period::where('status', PeriodStatus::Active)->first();
        $lapseId = $request->integer('lapse_id') ?: null;

        $config = $this->resolveConfig($section, $period?->id);

        abort_unless($config !== null, 404, 'No hay configuración de notas para este nivel educativo.');

        $sheet = new SectionGradeSheetResource($section, $config, $lapseId);

        $lapses = $period?->lapses()->orderBy('number')->get()->map(fn ($l) => [
            'id' => $l->id,
            'name' => $l->name,
        ]) ?? collect();

        return Inertia::render('professor/Grades/Sheet', [
            'sheet' => $sheet->toArray($request),
            'lapses' => $lapses,
            'current_lapse_id' => $lapseId,
            'visibility' => $request->user()->currentTeam?->grade_visibility?->value ?? 'real_time',
        ]);
    }

    public function upsertEntry(UpsertGradeEntryRequest $request, Section $section, UpsertGradeEntryAction $action): JsonResponse
    {
        $team = $request->user()->currentTeam;

        $entry = $action->handle(new GradeEntryWrapper($request->validated()), $team);

        return response()->json([
            'id' => $entry->id,
            'value' => $entry->value,
            'is_published' => $entry->is_published,
        ]);
    }

    public function publishSlot(Request $request, Section $section, PublishGradeSlotAction $action): RedirectResponse
    {
        Gate::authorize('publish', [Section::class, $section]);

        $slot = GradeSlot::findOrFail($request->integer('grade_slot_id'));
        $lapseId = $request->integer('lapse_id') ?: null;

        $action->handle($section, $slot, $lapseId);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Notas de \"{$slot->name}\" publicadas."]);

        return back();
    }

    private function resolveConfig(Section $section, ?int $periodId): ?GradeConfig
    {
        $level = $section->subject->pensum?->career?->educational_level
            ?? $section->educational_level
            ?? 'university';

        if ($periodId) {
            $config = GradeConfig::with('slots')
                ->where('level', $level)
                ->where('period_id', $periodId)
                ->first();

            if ($config) {
                return $config;
            }
        }

        return GradeConfig::with('slots')
            ->where('level', $level)
            ->whereNull('period_id')
            ->first();
    }
}
