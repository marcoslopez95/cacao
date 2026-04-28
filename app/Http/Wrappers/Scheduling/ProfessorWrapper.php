<?php

namespace App\Http\Wrappers\Scheduling;

use Illuminate\Support\Collection;

class ProfessorWrapper extends Collection
{
    public function getUserId(): int
    {
        return (int) $this->get('user_id');
    }

    public function getWeeklyHourLimit(): int
    {
        return (int) $this->get('weekly_hour_limit');
    }

    public function getActive(): bool
    {
        return (bool) $this->get('active');
    }
}
