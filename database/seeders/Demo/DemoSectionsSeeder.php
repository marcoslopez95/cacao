<?php

namespace Database\Seeders\Demo;

use App\Enums\ClassroomType;
use App\Enums\DayOfWeek;
use App\Enums\ScheduleSessionType;
use App\Enums\SectionType;
use App\Models\Classroom;
use App\Models\Period;
use App\Models\Professor;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class DemoSectionsSeeder extends Seeder
{
    /**
     * Day pairs for schedule rotation.
     *
     * @var array<int, array{0: DayOfWeek, 1: DayOfWeek}>
     */
    private array $dayPairs = [];

    /**
     * Time slots for schedule rotation.
     *
     * @var array<int, array{start: string, end: string}>
     */
    private array $timeSlots = [];

    /**
     * Seed course sections and their schedules.
     */
    public function run(): void
    {
        $this->dayPairs = [
            [DayOfWeek::Monday,  DayOfWeek::Wednesday],
            [DayOfWeek::Tuesday, DayOfWeek::Thursday],
            [DayOfWeek::Monday,  DayOfWeek::Wednesday],
            [DayOfWeek::Tuesday, DayOfWeek::Thursday],
            [DayOfWeek::Monday,  DayOfWeek::Friday],
        ];

        $this->timeSlots = [
            ['start' => '07:00:00', 'end' => '09:30:00'],
            ['start' => '09:30:00', 'end' => '12:00:00'],
            ['start' => '13:00:00', 'end' => '15:30:00'],
            ['start' => '15:30:00', 'end' => '18:00:00'],
        ];

        $period = Period::where('name', '2026-I')->first();

        if (! $period) {
            return;
        }

        /** @var Collection<int, Professor> $professors */
        $professors = Professor::all();

        /** @var Collection<int, Classroom> $classrooms */
        $classrooms = Classroom::where('type', ClassroomType::Theory)->get();

        if ($professors->isEmpty() || $classrooms->isEmpty()) {
            return;
        }

        $subjects = Subject::whereIn('period_number', [1, 2])
            ->orderBy('code')
            ->get();

        $sectionIndex = 0;

        foreach ($subjects as $subject) {
            $sectionCodes = $subject->period_number === 1
                ? ['A', 'B']
                : ['A'];

            foreach ($sectionCodes as $code) {
                $classroom = $classrooms[$sectionIndex % $classrooms->count()];
                $mainTeacher = $professors[$sectionIndex % $professors->count()];

                $section = Section::firstOrCreate(
                    [
                        'period_id' => $period->id,
                        'subject_id' => $subject->id,
                        'code' => $code,
                    ],
                    [
                        'type' => SectionType::University,
                        'theory_classroom_id' => $classroom->id,
                        'lab_classroom_id' => null,
                        'capacity' => 30,
                        'main_teacher_id' => $mainTeacher->id,
                    ],
                );

                // Update existing sections that were seeded before main_teacher_id was added
                if ($section->main_teacher_id === null) {
                    $section->update(['main_teacher_id' => $mainTeacher->id]);
                }

                $this->seedSchedules($section, $subject->id, $period, $professors, $classrooms, $sectionIndex);

                $sectionIndex++;
            }
        }
    }

    /**
     * Create two schedules (one per day in the pair) for a section.
     *
     * @param  Collection<int, Professor>  $professors
     * @param  Collection<int, Classroom>  $classrooms
     */
    private function seedSchedules(
        Section $section,
        int $subjectId,
        Period $period,
        Collection $professors,
        Collection $classrooms,
        int $sectionIndex,
    ): void {
        $dayPair = $this->dayPairs[$sectionIndex % count($this->dayPairs)];
        $timeSlot = $this->timeSlots[$sectionIndex % count($this->timeSlots)];
        $professor = $professors[$sectionIndex % $professors->count()];
        $classroom = $classrooms[$sectionIndex % $classrooms->count()];
        $validFrom = $period->start_date->toDateString();

        foreach ($dayPair as $day) {
            Schedule::firstOrCreate(
                [
                    'section_id' => $section->id,
                    'day_of_week' => $day,
                ],
                [
                    'professor_id' => $professor->id,
                    'classroom_id' => $classroom->id,
                    'subject_id' => $subjectId,
                    'start_time' => $timeSlot['start'],
                    'end_time' => $timeSlot['end'],
                    'type' => ScheduleSessionType::Theory,
                    'valid_from' => $validFrom,
                    'valid_until' => null,
                ],
            );
        }
    }
}
