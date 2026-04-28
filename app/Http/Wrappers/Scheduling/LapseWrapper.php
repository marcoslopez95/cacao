<?php

namespace App\Http\Wrappers\Scheduling;

use Illuminate\Support\Collection;

class LapseWrapper extends Collection
{
    public function getNumber(): int
    {
        return (int) $this->get('number');
    }

    public function getName(): string
    {
        return (string) $this->get('name');
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
