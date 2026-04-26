<?php

namespace App\Actions\Academic;

use App\Http\Wrappers\Academic\PensumWrapper;
use App\Models\Pensum;

class CreatePensumAction
{
    public function handle(PensumWrapper $wrapper): Pensum
    {
        return Pensum::create([
            'career_id' => $wrapper->getCareerId(),
            'name' => $wrapper->getName(),
            'period_type' => $wrapper->getPeriodType(),
            'total_periods' => $wrapper->getTotalPeriods(),
            'is_active' => $wrapper->isActive(),
        ]);
    }
}
