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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;

/**
 * Seed enrollment headers and details for the demo period.
 *
 * Creates up to 90 enrollments (one per student with current_pensum_id) distributed
 * across three status groups: Draft, Confirmed, and Approved.
 */
class DemoEnrollmentSeeder extends Seeder
{
    /**
     * Seed enrollment records and enrollment details.
     *
     * Creates up to 90 enrollments (30 per status group) with up to 4 details each.
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

        /** @var array<int, Collection<int, Subject>> $subjectsByPensum */
        $subjectsByPensum = [];

        foreach ($students as $index => $student) {
            $groupIndex = min((int) floor($index / 30), count($groups) - 1);
            $enrollmentStatus = $groups[$groupIndex]['enrollment'];
            $detailStatus = $groups[$groupIndex]['detail'];

            $pensumId = $student->current_pensum_id;

            if (! isset($subjectsByPensum[$pensumId])) {
                $subjectsByPensum[$pensumId] = Subject::where('pensum_id', $pensumId)
                    ->where('period_number', 1)
                    ->whereHas('sections', fn ($q) => $q->where('period_id', $period->id))
                    ->limit(4)
                    ->get();
            }

            $subjects = $subjectsByPensum[$pensumId];

            if ($subjects->isEmpty()) {
                continue;
            }

            // Collect (subject, section) pairs and compute ucTotal from what actually has a section
            $pairs = [];
            $ucTotal = 0;

            foreach ($subjects as $subject) {
                $section = Section::where('subject_id', $subject->id)
                    ->where('period_id', $period->id)
                    ->orderBy('code')
                    ->first();

                if (! $section) {
                    continue;
                }

                $pairs[] = ['subject' => $subject, 'section' => $section];
                $ucTotal += $subject->credits_uc;
            }

            if (empty($pairs)) {
                continue;
            }

            $enrollment = Enrollment::firstOrCreate(
                ['student_id' => $student->id, 'period_id' => $period->id],
                [
                    'pensum_id' => $pensumId,
                    'uc_disponibles' => $ucTotal,
                    'uc_inscritas' => $ucTotal,
                    'status' => $enrollmentStatus,
                ],
            );

            foreach ($pairs as $pair) {
                EnrollmentDetail::firstOrCreate(
                    ['enrollment_id' => $enrollment->id, 'subject_id' => $pair['subject']->id],
                    ['section_id' => $pair['section']->id, 'status' => $detailStatus],
                );
            }
        }
    }
}
