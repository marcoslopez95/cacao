<?php

namespace App\Actions\Scheduling;

use App\Http\Wrappers\Scheduling\ScheduleWrapper;
use App\Models\Schedule;

class UpdateScheduleAction
{
    public function handle(Schedule $schedule, ScheduleWrapper $data): void
    {
        $schedule->update([
            'section_id'   => $data->getSectionId(),
            'professor_id' => $data->getProfessorId(),
            'classroom_id' => $data->getClassroomId(),
            'subject_id'   => $data->getSubjectId(),
            'day_of_week'  => $data->getDayOfWeek(),
            'start_time'   => $data->getStartTime(),
            'end_time'     => $data->getEndTime(),
            'type'         => $data->getType(),
            'valid_from'   => $data->getValidFrom(),
            'valid_until'  => $data->getValidUntil(),
        ]);
    }
}
