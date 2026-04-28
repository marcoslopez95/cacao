<?php

namespace App\Http\Wrappers\Scheduling;

use Illuminate\Support\Collection;

class PeriodWrapper extends Collection
{
    public function getName(): string
    {
        return (string) $this->get('name');
    }

    public function getType(): string
    {
        return (string) $this->get('type');
    }

    public function getStartDate(): string
    {
        return (string) $this->get('start_date');
    }

    public function getEndDate(): string
    {
        return (string) $this->get('end_date');
    }
}
