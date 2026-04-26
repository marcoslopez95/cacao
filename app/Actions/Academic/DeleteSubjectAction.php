<?php

namespace App\Actions\Academic;

use App\Models\Subject;
use Illuminate\Support\Facades\DB;

class DeleteSubjectAction
{
    public function handle(Subject $subject): bool
    {
        return DB::transaction(function () use ($subject): bool {
            $hasDependents = $subject->dependents()->lockForUpdate()->exists();

            if ($hasDependents) {
                return false;
            }

            $subject->delete();

            return true;
        });
    }
}
