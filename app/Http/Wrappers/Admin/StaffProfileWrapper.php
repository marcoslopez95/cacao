<?php

namespace App\Http\Wrappers\Admin;

use Illuminate\Support\Collection;

class StaffProfileWrapper extends Collection
{
    public function __construct(array $validated)
    {
        parent::__construct($validated);
    }

    public function getAcademicTitle(): ?string
    {
        return $this->get('academic_title');
    }

    public function getSpecialty(): ?string
    {
        return $this->get('specialty');
    }

    public function getContractTypeId(): int
    {
        return (int) $this->get('contract_type_id');
    }

    public function getDedicationTypeId(): int
    {
        return (int) $this->get('dedication_type_id');
    }

    public function getWeeklyHourLoad(): ?int
    {
        $value = $this->get('weekly_hour_load');

        return $value !== null ? (int) $value : null;
    }

    public function getHireDate(): string
    {
        return $this->get('hire_date');
    }

    public function getTerminationDate(): ?string
    {
        return $this->get('termination_date');
    }

    public function getEmploymentStatusId(): int
    {
        return (int) $this->get('employment_status_id');
    }

    public function isCoordinator(): bool
    {
        return (bool) $this->get('is_coordinator', false);
    }

    public function getCoordinatedDepartmentId(): ?int
    {
        $value = $this->get('coordinated_department_id');

        return $value !== null ? (int) $value : null;
    }

    public function getCoordinatorSince(): ?string
    {
        return $this->get('coordinator_since');
    }
}
