<?php

namespace App\Actions\Scheduling;

use App\Enums\PeriodStatus;
use App\Models\Period;

class ClosePeriodAction
{
    public function handle(Period $period): bool
    {
        if (! $period->status->canTransitionTo(PeriodStatus::Closed)) {
            return false;
        }

        $period->update(['status' => PeriodStatus::Closed]);

        return true;
    }
}
