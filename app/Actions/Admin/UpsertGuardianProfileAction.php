<?php

namespace App\Actions\Admin;

use App\Http\Wrappers\Admin\GuardianProfileWrapper;
use App\Models\GuardianProfile;

class UpsertGuardianProfileAction
{
    public function handle(int $guardianId, GuardianProfileWrapper $wrapper): GuardianProfile
    {
        return GuardianProfile::updateOrCreate(
            ['guardian_id' => $guardianId],
            [
                'occupation' => $wrapper->getOccupation(),
                'employer' => $wrapper->getEmployer(),
                'work_phone' => $wrapper->getWorkPhone(),
                'education_level_id' => $wrapper->getEducationLevelId(),
                'marital_status_id' => $wrapper->getMaritalStatusId(),
            ]
        );
    }
}
