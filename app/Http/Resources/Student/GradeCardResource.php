<?php

namespace App\Http\Resources\Student;

use App\Enums\GradeVisibility;
use App\Models\Enrollment;
use App\Services\Grade\FinalGradeCalculator;
use App\Services\Grade\GradeConfigResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GradeCardResource extends JsonResource
{
    private readonly FinalGradeCalculator $finalGradeCalculator;

    private readonly GradeConfigResolver $gradeConfigResolver;

    public function __construct(
        private readonly Enrollment $enrollment,
        private readonly GradeVisibility $visibility,
    ) {
        parent::__construct($enrollment);

        $this->finalGradeCalculator = new FinalGradeCalculator;
        $this->gradeConfigResolver = new GradeConfigResolver;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $details = $this->enrollment->details()->with([
            'subject',
            'gradeEntries' => fn ($q) => $this->visibility === GradeVisibility::Manual
                ? $q->where('is_published', true)->with('children')
                : $q->with('children'),
        ])->get();

        $subjects = $details->map(function ($detail) {
            $config = $this->gradeConfigResolver->resolveForDetail($detail);

            if ($config === null) {
                return null;
            }

            $slots = $config->slots;
            $slotData = $slots->map(function ($slot) use ($detail) {
                $entry = $detail->gradeEntries
                    ->where('grade_slot_id', $slot->id)
                    ->whereNull('parent_id')
                    ->first();

                return [
                    'slot_id' => $slot->id,
                    'slot_name' => $slot->name,
                    'weight' => $slot->weight,
                    'value' => $entry?->value,
                    'is_published' => $entry?->is_published ?? false,
                    'is_remedial' => $slot->is_remedial,
                    'children' => $entry?->children->map(fn ($c) => [
                        'name' => $c->name,
                        'weight' => $c->weight,
                        'value' => $c->value,
                    ])->values()->all() ?? [],
                ];
            });

            $finalGrade = $this->finalGradeCalculator->calculate($slotData->toArray(), $config);

            return [
                'enrollment_detail_id' => $detail->id,
                'subject_name' => $detail->subject->name,
                'lapse_id' => null,
                'slots' => $slotData->values()->all(),
                'final_grade' => $finalGrade,
                'passed' => $finalGrade !== null
                    ? (float) $finalGrade >= (float) $config->passing_value
                    : null,
            ];
        })->filter()->values();

        return [
            'period' => $this->enrollment->period?->name ?? '—',
            'subjects' => $subjects,
        ];
    }
}
