<?php

namespace App\Actions\Admin;

use App\Enums\GradeVisibility;
use App\Models\EnrollmentDetail;
use App\Models\GradeConfig;
use App\Models\GradeEntry;
use App\Models\Team;

class EnableRemedialAction
{
    public function handle(EnrollmentDetail $detail, Team $team): ?GradeEntry
    {
        $level = $detail->enrollment->student->educational_level ?? 'university';
        $periodId = $detail->enrollment->period_id;

        $config = GradeConfig::with('slots')
            ->where('level', $level)
            ->where(fn ($q) => $q->where('period_id', $periodId)->orWhereNull('period_id'))
            ->orderByRaw('period_id IS NULL ASC')
            ->first();

        $remedialSlot = $config?->slots->firstWhere('is_remedial', true);

        if ($remedialSlot === null) {
            return null;
        }

        $isPublished = $team->grade_visibility === GradeVisibility::RealTime;

        return GradeEntry::firstOrCreate(
            [
                'enrollment_detail_id' => $detail->id,
                'grade_slot_id' => $remedialSlot->id,
                'lapse_id' => null,
                'parent_id' => null,
            ],
            [
                'value' => null,
                'is_published' => $isPublished,
            ]
        );
    }
}
