<?php

namespace App\Actions\Scheduling;

use App\Enums\PeriodStatus;
use App\Models\Period;

class DeletePeriodAction
{
    public function handle(Period $period): bool
    {
        if ($period->status !== PeriodStatus::Upcoming) {
            return false;
        }

        return (bool) $period->delete();
    }
}
