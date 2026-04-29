<?php

namespace App\Actions\Scheduling;

use App\Http\Wrappers\Scheduling\SchoolSectionWrapper;
use App\Models\Section;

class UpdateSchoolSectionAction
{
    public function handle(Section $section, SchoolSectionWrapper $wrapper): Section
    {
        $section->update([
            'letter'          => $wrapper->getLetter(),
            'code'            => $section->grade . $wrapper->getLetter(),
            'capacity'        => $wrapper->getCapacity(),
            'main_teacher_id' => $wrapper->getMainTeacherId(),
            'classroom_id'    => $wrapper->getClassroomId(),
        ]);

        return $section;
    }
}
