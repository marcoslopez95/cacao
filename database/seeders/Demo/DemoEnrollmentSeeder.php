<?php

namespace Database\Seeders\Demo;

use App\Enums\EnrollmentDetailStatus;
use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\EnrollmentDetail;
use App\Models\Period;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class DemoEnrollmentSeeder extends Seeder
{
    /**
     * Seed enrollment records and enrollment details.
     *
     * Creates 90 enrollments (30 per status group) with 4 details each.
     * Groups:
     *   - Students 0–29  → EnrollmentStatus::Draft,     details → EnrollmentDetailStatus::Draft
     *   - Students 30–59 → EnrollmentStatus::Confirmed, details → EnrollmentDetailStatus::Confirmed
     *   - Students 60–89 → EnrollmentStatus::Approved,  details → EnrollmentDetailStatus::Confirmed
     */
    public function run(): void
    {
        $period = Period::where('name', '2026-I')->firstOrFail();

        $students = Student::whereNotNull('current_pensum_id')
            ->orderBy('id')
            ->limit(90)
            ->get();

        /** @var array<int, array{enrollment: EnrollmentStatus, detail: EnrollmentDetailStatus}> $groups */
        $groups = [
            ['enrollment' => EnrollmentStatus::Draft,     'detail' => EnrollmentDetailStatus::Draft],
            ['enrollment' => EnrollmentStatus::Confirmed, 'detail' => EnrollmentDetailStatus::Confirmed],
            ['enrollment' => EnrollmentStatus::Approved,  'detail' => EnrollmentDetailStatus::Confirmed],
        ];

        foreach ($students as $index => $student) {
            $groupIndex = (int) floor($index / 30);
            $enrollmentStatus = $groups[$groupIndex]['enrollment'];
            $detailStatus = $groups[$groupIndex]['detail'];

            $subjects = Subject::where('pensum_id', $student->current_pensum_id)
                ->where('period_number', 1)
                ->whereHas('sections', fn ($q) => $q->where('period_id', $period->id))
                ->limit(4)
                ->get();

            if ($subjects->isEmpty()) {
                continue;
            }

            $ucTotal = $subjects->sum('credits_uc');

            $enrollment = Enrollment::firstOrCreate(
                ['student_id' => $student->id, 'period_id' => $period->id],
                [
                    'pensum_id' => $student->current_pensum_id,
                    'uc_disponibles' => $ucTotal,
                    'uc_inscritas' => $ucTotal,
                    'status' => $enrollmentStatus,
                ],
            );

            foreach ($subjects as $subject) {
                $section = Section::where('subject_id', $subject->id)
                    ->where('period_id', $period->id)
                    ->orderBy('code')
                    ->first();

                if (! $section) {
                    continue;
                }

                EnrollmentDetail::firstOrCreate(
                    ['enrollment_id' => $enrollment->id, 'subject_id' => $subject->id],
                    ['section_id' => $section->id, 'status' => $detailStatus],
                );
            }
        }
    }
}
