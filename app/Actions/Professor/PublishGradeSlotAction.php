<?php

namespace App\Actions\Professor;

use App\Models\GradeEntry;
use App\Models\GradeSlot;
use App\Models\Section;

class PublishGradeSlotAction
{
    public function handle(Section $section, GradeSlot $slot, ?int $lapseId = null): int
    {
        $enrollmentDetailIds = $section->enrollmentDetails()->pluck('id');

        return GradeEntry::whereIn('enrollment_detail_id', $enrollmentDetailIds)
            ->where('grade_slot_id', $slot->id)
            ->where('lapse_id', $lapseId)
            ->update(['is_published' => true]);
    }
}
