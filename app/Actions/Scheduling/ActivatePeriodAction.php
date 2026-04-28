<?php

namespace App\Actions\Scheduling;

use App\Enums\PeriodStatus;
use App\Models\Period;

class ActivatePeriodAction
{
    public function handle(Period $period): bool
    {
        if (! $period->status->canTransitionTo(PeriodStatus::Active)) {
            return false;
        }

        $period->update(['status' => PeriodStatus::Active]);

        return true;
    }
}
