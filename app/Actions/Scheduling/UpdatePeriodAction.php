<?php

namespace App\Actions\Scheduling;

use App\Http\Wrappers\Scheduling\PeriodWrapper;
use App\Models\Period;

class UpdatePeriodAction
{
    public function handle(Period $period, PeriodWrapper $wrapper): Period
    {
        $period->update([
            'name'       => $wrapper->getName(),
            'type'       => $wrapper->getType(),
            'start_date' => $wrapper->getStartDate(),
            'end_date'   => $wrapper->getEndDate(),
        ]);

        return $period;
    }
}
