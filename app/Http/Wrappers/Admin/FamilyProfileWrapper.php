<?php

namespace App\Http\Wrappers\Admin;

use Illuminate\Support\Collection;

class FamilyProfileWrapper extends Collection
{
    public function __construct(array $validated)
    {
        parent::__construct($validated);
    }

    public function getGuardianMaritalStatusId(): ?int
    {
        $value = $this->get('guardian_marital_status_id');

        return $value !== null ? (int) $value : null;
    }

    public function getChildrenCount(): ?int
    {
        $value = $this->get('children_count');

        return $value !== null ? (int) $value : null;
    }

    public function getSiblingPosition(): ?int
    {
        $value = $this->get('sibling_position');

        return $value !== null ? (int) $value : null;
    }

    public function getSiblingCount(): ?int
    {
        $value = $this->get('sibling_count');

        return $value !== null ? (int) $value : null;
    }

    public function getLivingArrangementId(): ?int
    {
        $value = $this->get('living_arrangement_id');

        return $value !== null ? (int) $value : null;
    }

    public function getHouseholdHeadTypeId(): ?int
    {
        $value = $this->get('household_head_type_id');

        return $value !== null ? (int) $value : null;
    }

    public function getHouseholdHeadName(): ?string
    {
        return $this->get('household_head_name');
    }
}
