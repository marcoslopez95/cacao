<?php

namespace App\Http\Wrappers\Admin;

use Illuminate\Support\Collection;

class HousingProfileWrapper extends Collection
{
    public function __construct(array $validated)
    {
        parent::__construct($validated);
    }

    public function getHousingTypeId(): ?int
    {
        $value = $this->get('housing_type_id');

        return $value !== null ? (int) $value : null;
    }

    public function getTenureTypeId(): ?int
    {
        $value = $this->get('tenure_type_id');

        return $value !== null ? (int) $value : null;
    }

    public function getConstructionMaterialId(): ?int
    {
        $value = $this->get('construction_material_id');

        return $value !== null ? (int) $value : null;
    }

    public function getRoomCount(): ?int
    {
        $value = $this->get('room_count');

        return $value !== null ? (int) $value : null;
    }

    public function getBathroomCount(): ?int
    {
        $value = $this->get('bathroom_count');

        return $value !== null ? (int) $value : null;
    }

    public function getHouseholdMembers(): ?int
    {
        $value = $this->get('household_members');

        return $value !== null ? (int) $value : null;
    }

    public function getCommuteTimeId(): ?int
    {
        $value = $this->get('commute_time_id');

        return $value !== null ? (int) $value : null;
    }

    public function getTransportTypeId(): ?int
    {
        $value = $this->get('transport_type_id');

        return $value !== null ? (int) $value : null;
    }

    /**
     * @return array<int, array{basic_service_id: int, is_available: bool}>
     */
    public function getServices(): array
    {
        return $this->get('services', []) ?? [];
    }

    public function hasServices(): bool
    {
        return $this->has('services') && $this->get('services') !== null;
    }
}
