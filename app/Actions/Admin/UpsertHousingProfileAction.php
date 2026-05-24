<?php

namespace App\Actions\Admin;

use App\Http\Wrappers\Admin\HousingProfileWrapper;
use App\Models\HousingProfile;
use App\Models\Student;
use App\Services\ConsentService;

class UpsertHousingProfileAction
{
    public function __construct(
        private readonly ConsentService $consentService,
    ) {}

    public function handle(Student $student, HousingProfileWrapper $wrapper): HousingProfile
    {
        $this->consentService->requireConsent($student->user);

        return HousingProfile::updateOrCreate(
            ['student_id' => $student->id],
            [
                'housing_type_id' => $wrapper->getHousingTypeId(),
                'tenure_type_id' => $wrapper->getTenureTypeId(),
                'construction_material_id' => $wrapper->getConstructionMaterialId(),
                'room_count' => $wrapper->getRoomCount(),
                'bathroom_count' => $wrapper->getBathroomCount(),
                'household_members' => $wrapper->getHouseholdMembers(),
                'commute_time_id' => $wrapper->getCommuteTimeId(),
                'transport_type_id' => $wrapper->getTransportTypeId(),
            ]
        );
    }
}
