<?php

namespace App\Actions\Scheduling;

use App\Http\Wrappers\Scheduling\ProfessorWrapper;
use App\Models\Professor;

class UpdateProfessorAction
{
    public function handle(Professor $professor, ProfessorWrapper $wrapper): Professor
    {
        $professor->update([
            'weekly_hour_limit' => $wrapper->getWeeklyHourLimit(),
            'active'            => $wrapper->getActive(),
        ]);

        return $professor;
    }
}
