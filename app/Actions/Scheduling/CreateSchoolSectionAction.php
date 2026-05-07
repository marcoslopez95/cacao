<?php

namespace App\Actions\Scheduling;

use App\Enums\SectionType;
use App\Http\Wrappers\Scheduling\SchoolSectionWrapper;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;

class CreateSchoolSectionAction
{
    public function handle(SchoolSectionWrapper $wrapper): Section
    {
        $grade  = $wrapper->getGrade();
        $letter = $wrapper->getLetter();

        $section = Section::create([
            'type'            => SectionType::School,
            'period_id'       => $wrapper->getPeriodId(),
            'pensum_id'       => $wrapper->getPensumId(),
            'grade'           => $grade,
            'letter'          => $letter,
            'code'            => $grade . $letter,
            'capacity'        => $wrapper->getCapacity(),
            'main_teacher_id' => $wrapper->getMainTeacherId(),
            'classroom_id'    => $wrapper->getClassroomId(),
        ]);

        $subjectIds = Subject::where('pensum_id', $section->pensum_id)
            ->where('period_number', $section->grade)
            ->pluck('id');

        if ($subjectIds->isNotEmpty()) {
            DB::table('section_subjects')->insert(
                $subjectIds->map(fn (int $id) => [
                    'section_id' => $section->id,
                    'subject_id' => $id,
                ])->all()
            );
        }

        return $section;
    }
}
