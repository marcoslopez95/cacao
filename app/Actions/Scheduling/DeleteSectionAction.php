<?php

namespace App\Actions\Scheduling;

use App\Models\Section;
use Illuminate\Support\Facades\DB;

class DeleteSectionAction
{
    public function handle(Section $section): bool
    {
        DB::table('section_subjects')->where('section_id', $section->id)->delete();

        return (bool) $section->delete();
    }
}
