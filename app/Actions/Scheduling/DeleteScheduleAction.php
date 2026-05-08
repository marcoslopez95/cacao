<?php

namespace App\Actions\Scheduling;

use App\Models\Schedule;

class DeleteScheduleAction
{
    public function handle(Schedule $schedule): void
    {
        $schedule->delete();
    }
}
