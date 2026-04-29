<?php

namespace App\Actions\Scheduling;

use App\Enums\SectionType;
use App\Http\Wrappers\Scheduling\UniversitySectionWrapper;
use App\Models\Section;

class CreateUniversitySectionAction
{
    public function handle(UniversitySectionWrapper $wrapper): Section
    {
        return Section::create([
            'type'                => SectionType::University,
            'period_id'           => $wrapper->getPeriodId(),
            'subject_id'          => $wrapper->getSubjectId(),
            'code'                => $wrapper->getCode(),
            'capacity'            => $wrapper->getCapacity(),
            'theory_classroom_id' => $wrapper->getTheoryClassroomId(),
            'lab_classroom_id'    => $wrapper->getLabClassroomId(),
        ]);
    }
}
