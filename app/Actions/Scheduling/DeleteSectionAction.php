<?php

namespace App\Actions\Scheduling;

use App\Models\Section;

class DeleteSectionAction
{
    public function handle(Section $section): bool
    {
        return (bool) $section->delete();
    }
}
