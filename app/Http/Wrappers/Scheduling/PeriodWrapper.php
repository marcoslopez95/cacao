<?php

namespace App\Http\Wrappers\Scheduling;

use App\Enums\PeriodType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PeriodWrapper extends Collection
{
    public function getName(): string
    {
        return (string) $this->get('name');
    }

    public function getType(): PeriodType
    {
        return PeriodType::from($this->get('type'));
    }

    public function getStartDate(): Carbon
    {
        return Carbon::parse($this->get('start_date'));
    }

    public function getEndDate(): Carbon
    {
        return Carbon::parse($this->get('end_date'));
    }
}
