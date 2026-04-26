<?php

namespace App\Http\Wrappers\Academic;

use Illuminate\Support\Collection;

class PensumWrapper extends Collection
{
    public function getCareerId(): int
    {
        return (int) $this->get('career_id');
    }

    public function getName(): string
    {
        return (string) $this->get('name');
    }

    public function getPeriodType(): string
    {
        return (string) $this->get('period_type');
    }

    public function getTotalPeriods(): int
    {
        return (int) $this->get('total_periods');
    }

    public function isActive(): bool
    {
        return (bool) ($this->get('is_active') ?? true);
    }
}
