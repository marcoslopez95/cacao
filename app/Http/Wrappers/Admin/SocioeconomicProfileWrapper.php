<?php

namespace App\Http\Wrappers\Admin;

use Illuminate\Support\Collection;

class SocioeconomicProfileWrapper extends Collection
{
    public function __construct(array $validated)
    {
        parent::__construct($validated);
    }

    public function getStudyDate(): string
    {
        return $this->get('study_date');
    }

    public function getIncomeRangeId(): ?int
    {
        $value = $this->get('income_range_id');

        return $value !== null ? (int) $value : null;
    }

    public function getIncomeSourceId(): ?int
    {
        $value = $this->get('income_source_id');

        return $value !== null ? (int) $value : null;
    }

    public function getHouseholdEarners(): ?int
    {
        $value = $this->get('household_earners');

        return $value !== null ? (int) $value : null;
    }

    public function getReceivesRemittances(): ?bool
    {
        $value = $this->get('receives_remittances');

        return $value !== null ? (bool) $value : null;
    }

    public function getRemittanceCountryId(): ?int
    {
        $value = $this->get('remittance_country_id');

        return $value !== null ? (int) $value : null;
    }

    public function getStudentWorks(): ?bool
    {
        $value = $this->get('student_works');

        return $value !== null ? (bool) $value : null;
    }

    public function getEmploymentTypeId(): ?int
    {
        $value = $this->get('employment_type_id');

        return $value !== null ? (int) $value : null;
    }

    public function getWeeklyWorkHours(): ?int
    {
        $value = $this->get('weekly_work_hours');

        return $value !== null ? (int) $value : null;
    }

    public function getHasScholarship(): ?bool
    {
        $value = $this->get('has_scholarship');

        return $value !== null ? (bool) $value : null;
    }

    public function getScholarshipName(): ?string
    {
        return $this->get('scholarship_name');
    }

    public function getHasInstitutionalBenefit(): ?bool
    {
        $value = $this->get('has_institutional_benefit');

        return $value !== null ? (bool) $value : null;
    }

    public function getRecordedBy(): ?int
    {
        $value = $this->get('recorded_by');

        return $value !== null ? (int) $value : null;
    }
}
