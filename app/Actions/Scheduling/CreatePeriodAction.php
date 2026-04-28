<?php

namespace App\Actions\Scheduling;

use App\Http\Wrappers\Scheduling\PeriodWrapper;
use App\Models\Period;

class CreatePeriodAction
{
    public function handle(PeriodWrapper $wrapper): Period
    {
        return Period::create([
            'name'       => $wrapper->getName(),
            'type'       => $wrapper->getType(),
            'start_date' => $wrapper->getStartDate(),
            'end_date'   => $wrapper->getEndDate(),
        ]);
    }
}
