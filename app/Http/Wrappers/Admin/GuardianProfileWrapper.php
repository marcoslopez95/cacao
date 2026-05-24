<?php

namespace App\Http\Wrappers\Admin;

use Illuminate\Support\Collection;

class GuardianProfileWrapper extends Collection
{
    public function __construct(array $validated)
    {
        parent::__construct($validated);
    }

    public function getOccupation(): ?string
    {
        return $this->get('occupation');
    }

    public function getEmployer(): ?string
    {
        return $this->get('employer');
    }

    public function getWorkPhone(): ?string
    {
        return $this->get('work_phone');
    }

    public function getEducationLevelId(): ?int
    {
        $value = $this->get('education_level_id');

        return $value !== null ? (int) $value : null;
    }

    public function getMaritalStatusId(): ?int
    {
        $value = $this->get('marital_status_id');

        return $value !== null ? (int) $value : null;
    }
}
