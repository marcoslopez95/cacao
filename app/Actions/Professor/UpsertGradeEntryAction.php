<?php

namespace App\Actions\Professor;

use App\Enums\GradeVisibility;
use App\Http\Wrappers\Professor\GradeEntryWrapper;
use App\Models\GradeEntry;
use App\Models\Team;

class UpsertGradeEntryAction
{
    public function __construct(
        private readonly CalculateSlotValueAction $calculator,
    ) {}

    public function handle(GradeEntryWrapper $wrapper, Team $team): GradeEntry
    {
        $isPublished = $team->grade_visibility === GradeVisibility::RealTime;

        $entry = GradeEntry::updateOrCreate(
            [
                'enrollment_detail_id' => $wrapper->getEnrollmentDetailId(),
                'grade_slot_id' => $wrapper->getGradeSlotId(),
                'lapse_id' => $wrapper->getLapseId(),
                'parent_id' => $wrapper->getParentId(),
                'name' => $wrapper->getName(),
            ],
            [
                'weight' => $wrapper->getWeight(),
                'value' => $wrapper->getValue(),
                'is_published' => $isPublished,
            ]
        );

        if ($wrapper->isSubEntry()) {
            $parent = GradeEntry::find($wrapper->getParentId());
            if ($parent !== null) {
                $this->calculator->handle($parent);
            }
        }

        return $entry;
    }
}
