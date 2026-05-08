<?php

namespace App\Policies;

use App\Models\Schedule;
use App\Models\User;

class SchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('schedules.view');
    }

    public function create(User $user): bool
    {
        return $user->can('schedules.create');
    }

    public function update(User $user, Schedule $schedule): bool
    {
        return $user->can('schedules.update');
    }

    public function delete(User $user, Schedule $schedule): bool
    {
        return $user->can('schedules.delete');
    }
}
