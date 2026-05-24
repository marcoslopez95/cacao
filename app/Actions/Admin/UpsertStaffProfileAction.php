<?php

namespace App\Actions\Admin;

use App\Http\Wrappers\Admin\StaffProfileWrapper;
use App\Models\StaffProfile;

class UpsertStaffProfileAction
{
    public function handle(int $professorId, StaffProfileWrapper $wrapper): StaffProfile
    {
        $profile = StaffProfile::where('professor_id', $professorId)->first();

        if ($profile === null) {
            $year = now()->year;
            $count = StaffProfile::whereYear('created_at', $year)->count() + 1;
            $employeeCode = sprintf('EMP-%d-%05d', $year, $count);

            $profile = StaffProfile::create([
                'professor_id' => $professorId,
                'employee_code' => $employeeCode,
                'academic_title' => $wrapper->getAcademicTitle(),
                'specialty' => $wrapper->getSpecialty(),
                'contract_type_id' => $wrapper->getContractTypeId(),
                'dedication_type_id' => $wrapper->getDedicationTypeId(),
                'weekly_hour_load' => $wrapper->getWeeklyHourLoad(),
                'hire_date' => $wrapper->getHireDate(),
                'termination_date' => $wrapper->getTerminationDate(),
                'employment_status_id' => $wrapper->getEmploymentStatusId(),
                'is_coordinator' => $wrapper->isCoordinator(),
                'coordinated_department_id' => $wrapper->getCoordinatedDepartmentId(),
                'coordinator_since' => $wrapper->getCoordinatorSince(),
            ]);
        } else {
            $profile->update([
                'academic_title' => $wrapper->getAcademicTitle(),
                'specialty' => $wrapper->getSpecialty(),
                'contract_type_id' => $wrapper->getContractTypeId(),
                'dedication_type_id' => $wrapper->getDedicationTypeId(),
                'weekly_hour_load' => $wrapper->getWeeklyHourLoad(),
                'hire_date' => $wrapper->getHireDate(),
                'termination_date' => $wrapper->getTerminationDate(),
                'employment_status_id' => $wrapper->getEmploymentStatusId(),
                'is_coordinator' => $wrapper->isCoordinator(),
                'coordinated_department_id' => $wrapper->getCoordinatedDepartmentId(),
                'coordinator_since' => $wrapper->getCoordinatorSince(),
            ]);
        }

        return $profile;
    }
}
