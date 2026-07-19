<?php

namespace App\Services\Grade;

use App\Models\Enrollment;
use App\Models\EnrollmentDetail;

class EnrollmentAverageCalculator
{
    public function __construct(
        private readonly FinalGradeCalculator $finalGradeCalculator = new FinalGradeCalculator,
        private readonly GradeConfigResolver $gradeConfigResolver = new GradeConfigResolver,
    ) {}

    /**
     * Calculate the simple average of the final grades of every subject in
     * an enrollment. Requires `details.subject` and `details.gradeEntries.children`
     * to be eager-loaded on the given enrollment.
     *
     * Returns null when no subject has a definitive final grade yet.
     */
    public function calculateForEnrollment(Enrollment $enrollment): ?string
    {
        $finalGrades = $enrollment->details
            ->map(fn ($detail) => $this->calculateFinalGradeForDetail($detail))
            ->filter(fn ($grade) => $grade !== null)
            ->values();

        if ($finalGrades->isEmpty()) {
            return null;
        }

        $average = $finalGrades->sum(fn ($grade) => (float) $grade) / $finalGrades->count();

        return (string) round($average, 2);
    }

    /**
     * @param  EnrollmentDetail  $detail
     */
    private function calculateFinalGradeForDetail($detail): ?string
    {
        $config = $this->gradeConfigResolver->resolveForDetail($detail);

        if ($config === null) {
            return null;
        }

        $slotData = $config->slots->map(function ($slot) use ($detail) {
            $entry = $detail->gradeEntries
                ->where('grade_slot_id', $slot->id)
                ->whereNull('parent_id')
                ->first();

            return [
                'slot_id' => $slot->id,
                'weight' => $slot->weight,
                'value' => $entry?->value,
                'is_remedial' => $slot->is_remedial,
            ];
        })->toArray();

        return $this->finalGradeCalculator->calculate($slotData, $config);
    }
}
