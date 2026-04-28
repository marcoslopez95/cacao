<?php

namespace App\Actions\Scheduling;

use App\Http\Wrappers\Scheduling\LapseWrapper;
use App\Models\Lapse;
use App\Models\Period;

class CreateLapseAction
{
    public function handle(Period $period, LapseWrapper $wrapper): Lapse
    {
        return Lapse::create([
            'period_id'  => $period->id,
            'number'     => $wrapper->getNumber(),
            'name'       => $wrapper->getName(),
            'start_date' => $wrapper->getStartDate(),
            'end_date'   => $wrapper->getEndDate(),
        ]);
    }
}
