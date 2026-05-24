<?php

namespace App\Http\Wrappers\Admin;

use Illuminate\Support\Collection;

class StudentBackgroundWrapper extends Collection
{
    public function __construct(array $validated)
    {
        parent::__construct($validated);
    }

    public function getPreviousInstitution(): ?string
    {
        return $this->get('previous_institution');
    }

    public function getInstitutionTypeId(): ?int
    {
        $value = $this->get('institution_type_id');

        return $value !== null ? (int) $value : null;
    }

    public function getGraduationYear(): ?int
    {
        $value = $this->get('graduation_year');

        return $value !== null ? (int) $value : null;
    }

    public function getPreviousGpa(): ?string
    {
        return $this->get('previous_gpa');
    }

    public function getRepeatedGrade(): ?bool
    {
        $value = $this->get('repeated_grade');

        return $value !== null ? (bool) $value : null;
    }

    public function getRepeatedGradeDescription(): ?string
    {
        return $this->get('repeated_grade_description');
    }

    public function getTransferReasonId(): ?int
    {
        $value = $this->get('transfer_reason_id');

        return $value !== null ? (int) $value : null;
    }

    public function getHasPriorStudies(): ?bool
    {
        $value = $this->get('has_prior_studies');

        return $value !== null ? (bool) $value : null;
    }

    public function getPriorStudiesDescription(): ?string
    {
        return $this->get('prior_studies_description');
    }

    public function getDigitalLevelId(): ?int
    {
        $value = $this->get('digital_level_id');

        return $value !== null ? (int) $value : null;
    }

    public function getMotherEducationLevelId(): ?int
    {
        $value = $this->get('mother_education_level_id');

        return $value !== null ? (int) $value : null;
    }

    public function getFatherEducationLevelId(): ?int
    {
        $value = $this->get('father_education_level_id');

        return $value !== null ? (int) $value : null;
    }
}
