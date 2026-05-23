<?php

namespace App\Http\Resources\Student;

use App\Enums\GradeVisibility;
use App\Models\Enrollment;
use App\Models\GradeConfig;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GradeCardResource extends JsonResource
{
    public function __construct(
        private readonly Enrollment $enrollment,
        private readonly GradeVisibility $visibility,
    ) {
        parent::__construct($enrollment);
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
            $config = $this->resolveConfig($detail);

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

            $finalGrade = $this->calculateFinalGrade($slotData->toArray(), $config);

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

    /**
     * @param  array<int, array<string, mixed>>  $slotData
     */
    private function calculateFinalGrade(array $slotData, GradeConfig $config): ?string
    {
        $nonRemedial = array_filter($slotData, fn ($s) => ! $s['is_remedial']);
        $allHaveValues = ! empty($nonRemedial) && collect($nonRemedial)->every(fn ($s) => $s['value'] !== null);

        if (! $allHaveValues) {
            return null;
        }

        $definitiva = collect($nonRemedial)->sum(
            fn ($s) => (float) $s['value'] * (float) $s['weight'] / 100
        );

        $remedialSlot = collect($slotData)->firstWhere('is_remedial', true);
        if ($remedialSlot && $remedialSlot['value'] !== null) {
            $definitiva = max($definitiva, (float) $remedialSlot['value']);
        }

        return (string) round($definitiva, 2);
    }

    private function resolveConfig($detail): ?GradeConfig
    {
        $level = $detail->enrollment->student->educational_level ?? 'university';
        $periodId = $detail->enrollment->period_id;

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
