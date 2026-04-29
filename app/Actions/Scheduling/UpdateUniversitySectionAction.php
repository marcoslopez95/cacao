<?php

namespace App\Actions\Scheduling;

use App\Http\Wrappers\Scheduling\UniversitySectionWrapper;
use App\Models\Section;

class UpdateUniversitySectionAction
{
    public function handle(Section $section, UniversitySectionWrapper $wrapper): Section
    {
        $section->update([
            'code'                => $wrapper->getCode(),
            'capacity'            => $wrapper->getCapacity(),
            'theory_classroom_id' => $wrapper->getTheoryClassroomId(),
            'lab_classroom_id'    => $wrapper->getLabClassroomId(),
        ]);

        return $section;
    }
}
