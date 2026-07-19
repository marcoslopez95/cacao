<?php

namespace App\Services\Grade;

use App\Models\GradeConfig;

class FinalGradeCalculator
{
    /**
     * Calculate the weighted final grade for a subject from its slot data.
     *
     * Non-remedial slots are averaged by weight; if a remedial slot has a
     * value, the final grade becomes the max between the weighted average
     * and the remedial value.
     *
     * @param  array<int, array<string, mixed>>  $slotData  shape: [slot_id, weight, value, is_remedial, ...]
     */
    public function calculate(array $slotData, GradeConfig $config): ?string
    {
        $nonRemedial = array_filter($slotData, fn ($s) => ! $s['is_remedial']);
        $allHaveValues = ! empty($nonRemedial) && collect($nonRemedial)->every(fn ($s) => $s['value'] !== null);

        if (! $allHaveValues) {
            return null;
        }

        $weightedTotal = collect($nonRemedial)->sum(
            fn ($s) => (float) $s['value'] * (float) $s['weight'] / 100
        );

        $remedialSlot = collect($slotData)->firstWhere('is_remedial', true);
        if ($remedialSlot && $remedialSlot['value'] !== null) {
            $weightedTotal = max($weightedTotal, (float) $remedialSlot['value']);
        }

        return (string) round($weightedTotal, 2);
    }
}
