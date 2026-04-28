<?php

namespace App\Actions\Scheduling;

use App\Http\Wrappers\Scheduling\ProfessorWrapper;
use App\Models\Professor;

class CreateProfessorAction
{
    public function handle(ProfessorWrapper $wrapper): Professor
    {
        return Professor::create([
            'user_id'           => $wrapper->getUserId(),
            'weekly_hour_limit' => $wrapper->getWeeklyHourLimit(),
        ]);
    }
}
