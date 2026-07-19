<?php

namespace Database\Seeders\Demo;

use App\Enums\ClassroomType;
use App\Enums\EducationalLevel;
use App\Enums\EnrollmentDetailStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\GradeLevel;
use App\Enums\GradeScaleType;
use App\Enums\SectionType;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\EnrollmentDetail;
use App\Models\GradeConfig;
use App\Models\GradeEntry;
use App\Models\GradeSlot;
use App\Models\Lapse;
use App\Models\Pensum;
use App\Models\Period;
use App\Models\Professor;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Backfills academic history for every enrolled student: for every past
 * period that should already have been completed, creates the historical
 * Section (ad-hoc, reusing professors/classrooms), Enrollment::Approved,
 * EnrollmentDetail::Confirmed, and published GradeEntry rows.
 *
 * See specs/demo-seeder-realism/design.md for the temporal mapping formula.
 */
class DemoAcademicHistorySeeder extends Seeder
{
    /**
     * Chronological university semester index (1-8) → Period name.
     *
     * @var array<int, string>
     */
    private const UNIVERSITY_PERIODS = [
        1 => '2022-II',
        2 => '2023-I',
        3 => '2023-II',
        4 => '2024-I',
        5 => '2024-II',
        6 => '2025-I',
        7 => '2025-II',
        8 => '2026-I',
    ];

    /**
     * School year number (1-5) → Period name.
     *
     * @var array<int, string>
     */
    private const SCHOOL_YEAR_PERIODS = [
        1 => '2021-2022',
        2 => '2022-2023',
        3 => '2023-2024',
        4 => '2024-2025',
        5 => '2025-2026',
    ];

    /** @var Collection<int, Professor> */
    private Collection $professors;

    /** @var Collection<int, Classroom> */
    private Collection $classrooms;

    private GradeConfig $universityGradeConfig;

    private GradeConfig $secondaryGradeConfig;

    /** @var array<string, Period|null> */
    private array $periodCache = [];

    /** @var array<string, Collection<int, Subject>> */
    private array $subjectCache = [];

    /** @var array<int, Collection<int, Lapse>> */
    private array $lapseCache = [];

    /** @var array<string, Section> */
    private array $universitySectionCache = [];

    /** @var array<string, Section> */
    private array $schoolSectionCache = [];

    public function run(): void
    {
        $this->professors = Professor::all();
        $this->classrooms = Classroom::where('type', ClassroomType::Theory)->get();

        if ($this->professors->isEmpty() || $this->classrooms->isEmpty()) {
            return;
        }

        $this->universityGradeConfig = $this->seedGradeConfig(
            GradeLevel::University,
            ['min' => '0.00', 'max' => '20.00', 'passing' => '10.00'],
            [
                ['name' => 'Primer Parcial', 'weight' => '30.00', 'sort_order' => 1],
                ['name' => 'Segundo Parcial', 'weight' => '30.00', 'sort_order' => 2],
                ['name' => 'Examen Final', 'weight' => '40.00', 'sort_order' => 3],
            ],
        );

        $this->secondaryGradeConfig = $this->seedGradeConfig(
            GradeLevel::PrimarySecondary,
            ['min' => '1.00', 'max' => '20.00', 'passing' => '10.00'],
            [
                ['name' => 'Primer Momento', 'weight' => '34.00', 'sort_order' => 1],
                ['name' => 'Segundo Momento', 'weight' => '33.00', 'sort_order' => 2],
                ['name' => 'Tercer Momento', 'weight' => '33.00', 'sort_order' => 3],
            ],
        );

        Student::whereNotNull('current_pensum_id')
            ->orderBy('id')
            ->chunkById(100, function (Collection $students): void {
                $this->seedHistoryForChunk($students);
            });
    }

    /**
     * Builds every (enrollment, enrollment_detail, grade_entry) row implied by
     * this chunk of students in memory, then persists each level with one
     * bulk insert instead of per-row Eloquent creates.
     *
     * @param  Collection<int, Student>  $students
     */
    private function seedHistoryForChunk(Collection $students): void
    {
        $plan = [];

        foreach ($students as $student) {
            $profile = $this->profileForStudent($student->id);
            $isUniversity = $student->educational_level === EducationalLevel::University;
            $tasks = $isUniversity ? $this->universityTasksFor($student) : $this->schoolTasksFor($student);

            foreach ($tasks as [$periodName, $periodNumber]) {
                $period = $this->getPeriod($periodName);

                if (! $period) {
                    continue;
                }

                $item = $isUniversity
                    ? $this->buildUniversityPlanItem($student, $period, $periodNumber, $profile)
                    : $this->buildSchoolPlanItem($student, $period, $periodNumber, $profile);

                if ($item !== null) {
                    $plan[] = $item;
                }
            }
        }

        if ($plan !== []) {
            $this->persistPlan($plan);
        }
    }

    /**
     * @return array<int, array{0: string, 1: int}>
     */
    private function universityTasksFor(Student $student): array
    {
        $currentSemester = $student->academic_year * 2;
        $tasks = [];

        for ($periodNumber = 1; $periodNumber < $currentSemester; $periodNumber++) {
            $chronologicalIndex = 8 - ($currentSemester - $periodNumber);
            $periodName = self::UNIVERSITY_PERIODS[$chronologicalIndex] ?? null;

            if ($periodName !== null) {
                $tasks[] = [$periodName, $periodNumber];
            }
        }

        return $tasks;
    }

    /**
     * @return array<int, array{0: string, 1: int}>
     */
    private function schoolTasksFor(Student $student): array
    {
        $currentYear = $student->academic_year;
        $tasks = [];

        for ($yearNumber = 1; $yearNumber < $currentYear; $yearNumber++) {
            $periodName = self::SCHOOL_YEAR_PERIODS[$yearNumber] ?? null;

            if ($periodName !== null) {
                $tasks[] = [$periodName, $yearNumber];
            }
        }

        return $tasks;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildUniversityPlanItem(Student $student, Period $period, int $periodNumber, string $profile): ?array
    {
        $subjects = $this->getSubjectsFor($student->current_pensum_id, $periodNumber);

        if ($subjects->isEmpty()) {
            return null;
        }

        $sections = $subjects->mapWithKeys(fn (Subject $subject) => [
            $subject->id => $this->findOrCreateUniversitySection($period, $subject),
        ]);

        return [
            'student_id' => $student->id,
            'period_id' => $period->id,
            'pensum_id' => $student->current_pensum_id,
            'uc' => (int) $subjects->sum('credits_uc'),
            'subjects' => $subjects,
            'sections' => $sections,
            'config' => $this->universityGradeConfig,
            'lapses' => null,
            'profile' => $profile,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildSchoolPlanItem(Student $student, Period $period, int $yearNumber, string $profile): ?array
    {
        $pensum = Pensum::find($student->current_pensum_id);

        if (! $pensum) {
            return null;
        }

        $subjects = $this->getSubjectsFor($pensum->id, $yearNumber);

        if ($subjects->isEmpty()) {
            return null;
        }

        $section = $this->findOrCreateSchoolSection($period, $pensum, $yearNumber);
        $sections = $subjects->mapWithKeys(fn (Subject $subject) => [$subject->id => $section]);

        return [
            'student_id' => $student->id,
            'period_id' => $period->id,
            'pensum_id' => $pensum->id,
            'uc' => (int) $subjects->sum('credits_uc'),
            'subjects' => $subjects,
            'sections' => $sections,
            'config' => $this->secondaryGradeConfig,
            'lapses' => $this->getLapsesFor($period->id),
            'profile' => $profile,
        ];
    }

    /**
     * Persists one chunk's worth of plan items level by level: enrollments,
     * then enrollment_details, then grade_entries. Each level is bulk
     * inserted with `insertOrIgnore` (both tables carry a unique constraint,
     * so a second seeder run is naturally idempotent) and IDs are recovered
     * with a single follow-up `whereIn` query instead of per-row round trips.
     *
     * @param  array<int, array<string, mixed>>  $plan
     */
    private function persistPlan(array $plan): void
    {
        $now = now();

        $enrollmentRows = [];

        foreach ($plan as $item) {
            $key = $item['student_id'].':'.$item['period_id'];

            $enrollmentRows[$key] ??= [
                'student_id' => $item['student_id'],
                'period_id' => $item['period_id'],
                'pensum_id' => $item['pensum_id'],
                'uc_disponibles' => $item['uc'],
                'uc_inscritas' => $item['uc'],
                'status' => EnrollmentStatus::Approved->value,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk(array_values($enrollmentRows), 500) as $chunk) {
            DB::table('enrollments')->insertOrIgnore($chunk);
        }

        $studentIds = array_unique(array_column($plan, 'student_id'));

        $enrollmentIdByKey = Enrollment::whereIn('student_id', $studentIds)
            ->get(['id', 'student_id', 'period_id'])
            ->keyBy(fn (Enrollment $enrollment) => $enrollment->student_id.':'.$enrollment->period_id);

        $detailRows = [];
        $detailMetaBySubjectKey = [];

        foreach ($plan as $item) {
            $enrollment = $enrollmentIdByKey->get($item['student_id'].':'.$item['period_id']);

            if (! $enrollment) {
                continue;
            }

            foreach ($item['subjects'] as $subject) {
                $section = $item['sections'][$subject->id];
                $key = $enrollment->id.':'.$subject->id;

                $detailRows[$key] ??= [
                    'enrollment_id' => $enrollment->id,
                    'subject_id' => $subject->id,
                    'section_id' => $section->id,
                    'status' => EnrollmentDetailStatus::Confirmed->value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $detailMetaBySubjectKey[$key] = [
                    'config' => $item['config'],
                    'lapses' => $item['lapses'],
                    'profile' => $item['profile'],
                ];
            }
        }

        foreach (array_chunk(array_values($detailRows), 500) as $chunk) {
            DB::table('enrollment_details')->insertOrIgnore($chunk);
        }

        $enrollmentIds = array_unique($enrollmentIdByKey->pluck('id')->all());

        $detailIdBySubjectKey = EnrollmentDetail::whereIn('enrollment_id', $enrollmentIds)
            ->get(['id', 'enrollment_id', 'subject_id'])
            ->keyBy(fn (EnrollmentDetail $detail) => $detail->enrollment_id.':'.$detail->subject_id);

        $this->persistGradeEntries($detailIdBySubjectKey, $detailMetaBySubjectKey, $now);
    }

    /**
     * @param  Collection<string, EnrollmentDetail>  $detailIdBySubjectKey
     * @param  array<string, array{config: GradeConfig, lapses: Collection<int, Lapse>|null, profile: string}>  $detailMetaBySubjectKey
     */
    private function persistGradeEntries(Collection $detailIdBySubjectKey, array $detailMetaBySubjectKey, CarbonInterface $now): void
    {
        $detailIds = $detailIdBySubjectKey->pluck('id')->all();

        $existingGradeKeys = GradeEntry::whereIn('enrollment_detail_id', $detailIds)
            ->whereNull('parent_id')
            ->get(['enrollment_detail_id', 'grade_slot_id', 'lapse_id'])
            ->map(fn (GradeEntry $entry) => $entry->enrollment_detail_id.':'.$entry->grade_slot_id.':'.$entry->lapse_id)
            ->flip();

        $gradeRows = [];

        foreach ($detailMetaBySubjectKey as $key => $meta) {
            $detail = $detailIdBySubjectKey->get($key);

            if (! $detail) {
                continue;
            }

            $range = $this->gradeRangeForProfile($meta['profile']);

            foreach ($meta['config']->slots as $slot) {
                $lapseId = $meta['lapses'] !== null
                    ? optional($meta['lapses']->get($slot->sort_order - 1))->id
                    : null;

                $gradeKey = $detail->id.':'.$slot->id.':'.$lapseId;

                if ($existingGradeKeys->has($gradeKey)) {
                    continue;
                }

                $existingGradeKeys->put($gradeKey, true);

                $gradeRows[] = [
                    'enrollment_detail_id' => $detail->id,
                    'grade_slot_id' => $slot->id,
                    'lapse_id' => $lapseId,
                    'parent_id' => null,
                    'name' => null,
                    'weight' => $slot->weight,
                    'value' => round(fake()->randomFloat(2, $range[0], $range[1]), 2),
                    'is_published' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($gradeRows, 500) as $chunk) {
            DB::table('grade_entries')->insert($chunk);
        }
    }

    private function findOrCreateUniversitySection(Period $period, Subject $subject): Section
    {
        $key = $period->id.':'.$subject->id;

        if (isset($this->universitySectionCache[$key])) {
            return $this->universitySectionCache[$key];
        }

        $classroom = $this->classrooms[$subject->id % $this->classrooms->count()];
        $teacher = $this->professors[$subject->id % $this->professors->count()];

        return $this->universitySectionCache[$key] = Section::firstOrCreate(
            ['period_id' => $period->id, 'subject_id' => $subject->id, 'code' => 'A'],
            [
                'type' => SectionType::University,
                'theory_classroom_id' => $classroom->id,
                'lab_classroom_id' => null,
                'capacity' => 30,
                'main_teacher_id' => $teacher->id,
            ],
        );
    }

    private function findOrCreateSchoolSection(Period $period, Pensum $pensum, int $grade): Section
    {
        $key = $period->id.':'.$pensum->id.':'.$grade;

        if (isset($this->schoolSectionCache[$key])) {
            return $this->schoolSectionCache[$key];
        }

        $classroom = $this->classrooms[$grade % $this->classrooms->count()];
        $teacher = $this->professors[$grade % $this->professors->count()];

        $section = Section::firstOrCreate(
            ['period_id' => $period->id, 'pensum_id' => $pensum->id, 'grade' => $grade, 'letter' => 'A'],
            [
                'type' => SectionType::School,
                'subject_id' => null,
                'code' => $grade.'A',
                'capacity' => 30,
                'main_teacher_id' => $teacher->id,
                'classroom_id' => $classroom->id,
            ],
        );

        $subjectIds = $this->getSubjectsFor($pensum->id, $grade)->pluck('id');

        if ($subjectIds->isNotEmpty()) {
            DB::table('section_subjects')->insertOrIgnore(
                $subjectIds->map(fn (int $subjectId) => [
                    'section_id' => $section->id,
                    'subject_id' => $subjectId,
                ])->all()
            );
        }

        return $this->schoolSectionCache[$key] = $section;
    }

    /**
     * @param  array{min: string, max: string, passing: string}  $scale
     * @param  array<int, array{name: string, weight: string, sort_order: int}>  $slots
     */
    private function seedGradeConfig(GradeLevel $level, array $scale, array $slots): GradeConfig
    {
        $config = GradeConfig::firstOrCreate(
            ['level' => $level, 'period_id' => null],
            [
                'scale_type' => GradeScaleType::Numeric,
                'scale_min' => $scale['min'],
                'scale_max' => $scale['max'],
                'passing_value' => $scale['passing'],
            ],
        );

        foreach ($slots as $slotData) {
            GradeSlot::firstOrCreate(
                ['grade_config_id' => $config->id, 'name' => $slotData['name']],
                [
                    'weight' => $slotData['weight'],
                    'sort_order' => $slotData['sort_order'],
                    'is_remedial' => false,
                ],
            );
        }

        return $config->load('slots');
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function gradeRangeForProfile(string $profile): array
    {
        return match ($profile) {
            'bueno' => fake()->boolean(5) ? [5.0, 9.0] : [15.0, 19.0],
            'regular' => fake()->boolean(15) ? [5.0, 9.0] : [10.0, 16.0],
            default => fake()->boolean(35) ? [6.0, 9.0] : [10.0, 14.0],
        };
    }

    /**
     * Deterministic per-student profile (30% bueno / 50% regular / 20% en riesgo)
     * so repeated seeder runs keep the same grade-generation bias per student.
     */
    private function profileForStudent(int $studentId): string
    {
        $bucket = crc32('demo-grade-profile-'.$studentId) % 100;

        return match (true) {
            $bucket < 30 => 'bueno',
            $bucket < 80 => 'regular',
            default => 'en_riesgo',
        };
    }

    private function getPeriod(string $name): ?Period
    {
        return $this->periodCache[$name] ??= Period::where('name', $name)->first();
    }

    /**
     * @return Collection<int, Subject>
     */
    private function getSubjectsFor(int $pensumId, int $periodNumber): Collection
    {
        $key = $pensumId.':'.$periodNumber;

        return $this->subjectCache[$key] ??= Subject::where('pensum_id', $pensumId)
            ->where('period_number', $periodNumber)
            ->get();
    }

    /**
     * @return Collection<int, Lapse>
     */
    private function getLapsesFor(int $periodId): Collection
    {
        return $this->lapseCache[$periodId] ??= Lapse::where('period_id', $periodId)
            ->orderBy('number')
            ->get();
    }
}
