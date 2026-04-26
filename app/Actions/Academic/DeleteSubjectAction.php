<?php

namespace App\Actions\Academic;

use App\Models\Subject;

class DeleteSubjectAction
{
    public function handle(Subject $subject): bool
    {
        if ($subject->dependents()->exists()) {
            return false;
        }

        $subject->delete();

        return true;
    }
}
