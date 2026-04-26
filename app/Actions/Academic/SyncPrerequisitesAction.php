<?php

namespace App\Actions\Academic;

use App\Models\Subject;

class SyncPrerequisitesAction
{
    /**
     * @param  array<int>  $prerequisiteIds
     */
    public function handle(Subject $subject, array $prerequisiteIds): Subject
    {
        $subject->prerequisites()->sync($prerequisiteIds);
        $subject->load('prerequisites');

        return $subject;
    }
}
