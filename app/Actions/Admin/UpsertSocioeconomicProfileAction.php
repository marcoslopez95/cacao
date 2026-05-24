<?php

namespace App\Actions\Admin;

use App\Http\Wrappers\Admin\SocioeconomicProfileWrapper;
use App\Models\SocioeconomicProfile;
use App\Models\Student;
use App\Services\ConsentService;

class UpsertSocioeconomicProfileAction
{
    public function __construct(
        private readonly ConsentService $consentService,
    ) {}

    public function handle(Student $student, SocioeconomicProfileWrapper $wrapper): SocioeconomicProfile
    {
        $this->consentService->requireConsent($student->user);

        return SocioeconomicProfile::updateOrCreate(
            ['student_id' => $student->id],
            [
                'income_range_id' => $wrapper->getIncomeRangeId(),
                'income_source_id' => $wrapper->getIncomeSourceId(),
                'household_earners' => $wrapper->getHouseholdEarners(),
                'receives_remittances' => $wrapper->getReceivesRemittances(),
                'remittance_country_id' => $wrapper->getRemittanceCountryId(),
                'student_works' => $wrapper->getStudentWorks(),
                'employment_type_id' => $wrapper->getEmploymentTypeId(),
                'weekly_work_hours' => $wrapper->getWeeklyWorkHours(),
                'has_scholarship' => $wrapper->getHasScholarship(),
                'scholarship_name' => $wrapper->getScholarshipName(),
                'has_institutional_benefit' => $wrapper->getHasInstitutionalBenefit(),
                'recorded_by' => $wrapper->getRecordedBy(),
                'study_date' => $wrapper->getStudyDate(),
            ]
        );
    }
}
