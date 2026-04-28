<?php

namespace App\Actions\Scheduling;

use App\Http\Wrappers\Scheduling\LapseWrapper;
use App\Models\Lapse;

class UpdateLapseAction
{
    public function handle(Lapse $lapse, LapseWrapper $wrapper): Lapse
    {
        $lapse->update([
            'number'     => $wrapper->getNumber(),
            'name'       => $wrapper->getName(),
            'start_date' => $wrapper->getStartDate(),
            'end_date'   => $wrapper->getEndDate(),
        ]);

        return $lapse;
    }
}
