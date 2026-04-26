<?php

namespace App\Actions\Academic;

use App\Http\Wrappers\Academic\SubjectWrapper;
use App\Models\Subject;

class UpdateSubjectAction
{
    public function handle(Subject $subject, SubjectWrapper $wrapper): Subject
    {
        $subject->update([
            'name' => $wrapper->getName(),
            'code' => $wrapper->getCode(),
            'credits_uc' => $wrapper->getCreditsUc(),
            'period_number' => $wrapper->getPeriodNumber(),
            'description' => $wrapper->getDescription(),
        ]);

        return $subject;
    }
}
