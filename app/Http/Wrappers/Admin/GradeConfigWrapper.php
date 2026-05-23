<?php

namespace App\Http\Wrappers\Admin;

use App\Enums\GradeLevel;
use App\Enums\GradeScaleType;
use Illuminate\Support\Collection;

class GradeConfigWrapper extends Collection
{
    public function getLevel(): GradeLevel
    {
        return GradeLevel::from($this->get('level'));
    }

    public function getScaleType(): GradeScaleType
    {
        return GradeScaleType::from($this->get('scale_type'));
    }

    public function getPeriodId(): ?int
    {
        return $this->get('period_id');
    }

    public function getScaleMin(): ?string
    {
        return $this->get('scale_min');
    }

    public function getScaleMax(): ?string
    {
        return $this->get('scale_max');
    }

    public function getPassingValue(): string
    {
        return $this->get('passing_value');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getSlots(): array
    {
        return $this->get('slots', []);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getLetterValues(): array
    {
        return $this->get('letter_values', []);
    }

    public function isLetterScale(): bool
    {
        return $this->getScaleType() === GradeScaleType::Letter;
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfigData(): array
    {
        return [
            'level' => $this->getLevel(),
            'period_id' => $this->getPeriodId(),
            'scale_type' => $this->getScaleType(),
            'scale_min' => $this->getScaleMin(),
            'scale_max' => $this->getScaleMax(),
            'passing_value' => $this->getPassingValue(),
        ];
    }
}
