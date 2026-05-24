<?php

namespace App\Actions\Admin;

use App\Http\Wrappers\Admin\FamilyProfileWrapper;
use App\Models\FamilyProfile;

class UpsertFamilyProfileAction
{
    public function handle(int $studentId, FamilyProfileWrapper $wrapper): FamilyProfile
    {
        return FamilyProfile::updateOrCreate(
            ['student_id' => $studentId],
            [
                'guardian_marital_status_id' => $wrapper->getGuardianMaritalStatusId(),
                'children_count' => $wrapper->getChildrenCount(),
                'sibling_position' => $wrapper->getSiblingPosition(),
                'sibling_count' => $wrapper->getSiblingCount(),
                'living_arrangement_id' => $wrapper->getLivingArrangementId(),
                'household_head_type_id' => $wrapper->getHouseholdHeadTypeId(),
                'household_head_name' => $wrapper->getHouseholdHeadName(),
            ]
        );
    }
}
