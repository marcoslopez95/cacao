<?php

namespace App\Http\Wrappers\Security;

use Illuminate\Support\Collection;

class CoordinationWrapper extends Collection
{
    public function getName(): string
    {
        return $this->get('name');
    }

    public function getType(): string
    {
        return $this->get('type');
    }

    public function getEducationLevel(): string
    {
        return $this->get('education_level');
    }

    public function getSecondaryType(): ?string
    {
        return $this->get('secondary_type');
    }

    public function getCareerId(): ?int
    {
        return $this->get('career_id');
    }

    public function getGradeYear(): ?int
    {
        return $this->get('grade_year');
    }

    public function getActive(): ?bool
    {
        return $this->has('active') ? (bool) $this->get('active') : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function getStoreData(): array
    {
        return [
            'name' => $this->getName(),
            'type' => $this->getType(),
            'education_level' => $this->getEducationLevel(),
            'secondary_type' => $this->getSecondaryType(),
            'career_id' => $this->getCareerId(),
            'grade_year' => $this->getGradeYear(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getUpdateData(): array
    {
        $data = array_filter([
            'name' => $this->getName(),
            'type' => $this->getType(),
            'education_level' => $this->getEducationLevel(),
            'secondary_type' => $this->getSecondaryType(),
            'career_id' => $this->getCareerId(),
            'grade_year' => $this->getGradeYear(),
        ], fn (mixed $v) => $v !== null);

        if ($this->getActive() !== null) {
            $data['active'] = $this->getActive();
        }

        return $data;
    }
}
