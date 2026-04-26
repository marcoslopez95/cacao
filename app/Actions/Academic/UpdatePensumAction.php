<?php

namespace App\Actions\Academic;

use App\Http\Wrappers\Academic\PensumWrapper;
use App\Models\Pensum;

class UpdatePensumAction
{
    public function handle(Pensum $pensum, PensumWrapper $wrapper): Pensum
    {
        $pensum->update([
            'name' => $wrapper->getName(),
            'period_type' => $wrapper->getPeriodType(),
            'total_periods' => $wrapper->getTotalPeriods(),
            'is_active' => $wrapper->isActive(),
        ]);

        return $pensum;
    }
}
