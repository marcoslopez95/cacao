<?php

namespace Database\Seeders\Demo;

use App\Enums\EducationalLevel;
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
 * Seed enrollment headers and details for the current period, for both
 * university and secondary students.
 *
 * Every student with a `current_pensum_id` is deterministically bucketed
 * (via a hash of their id) into one of three groups for the current
 * period/school year:
 *   - "sin"       — no Enrollment at all for the current period.
 *   - "a_medias"  — Enrollment (Draft or Confirmed) with a partial subset
 *                   of the subjects that correspond to the student.
 *   - "completa"  — Enrollment::Approved with 100% of the corresponding
 *                   subjects, EnrollmentDetail::Confirmed.
 */
class DemoEnrollmentSeeder extends Seeder
{
    public function run(): void
    {
        $universityPeriod = Period::where('name', '2026-I')->first();
        $schoolPeriod = Period::where('name', '2025-2026')->first();

        if (! $universityPeriod || ! $schoolPeriod) {
            return;
        }

        $students = Student::whereNotNull('current_pensum_id')->orderBy('id')->get();

        foreach ($students as $student) {
            $this->seedForStudent($student, $universityPeriod, $schoolPeriod);
        }
    }

    private function seedForStudent(Student $student, Period $universityPeriod, Period $schoolPeriod): void
    {
        if ($this->enrollmentGroup($student->id) === 'sin') {
            return;
        }

        $isUniversity = $student->educational_level === EducationalLevel::University;
        $period = $isUniversity ? $universityPeriod : $schoolPeriod;
        $currentPeriodNumber = $isUniversity ? $student->academic_year * 2 : $student->academic_year;

        $subjects = Subject::where('pensum_id', $student->current_pensum_id)
            ->where('period_number', $currentPeriodNumber)
            ->get();

        if ($subjects->isEmpty()) {
            return;
        }

        $pairs = $this->resolveSubjectSectionPairs($subjects, $period, $isUniversity);

        if (empty($pairs)) {
            return;
        }

        [$selected, $enrollmentStatus, $detailStatus] = $this->resolveSelectionForGroup(
            $this->enrollmentGroup($student->id),
            $pairs,
            $student->id,
        );

        if (empty($selected)) {
            return;
        }

        $ucTotal = array_sum(array_map(
            fn (array $pair): int => (int) $pair['subject']->credits_uc,
            $selected,
        ));

        $enrollment = Enrollment::firstOrCreate(
            ['student_id' => $student->id, 'period_id' => $period->id],
            [
                'pensum_id' => $student->current_pensum_id,
                'uc_disponibles' => $ucTotal,
                'uc_inscritas' => $ucTotal,
                'status' => $enrollmentStatus,
            ],
        );

        foreach ($selected as $pair) {
            EnrollmentDetail::firstOrCreate(
                ['enrollment_id' => $enrollment->id, 'subject_id' => $pair['subject']->id],
                ['section_id' => $pair['section']->id, 'status' => $detailStatus],
            );
        }
    }

    /**
     * @param  array<int, array{subject: Subject, section: Section}>  $pairs
     * @return array{0: array<int, array{subject: Subject, section: Section}>, 1: EnrollmentStatus, 2: EnrollmentDetailStatus}
     */
    private function resolveSelectionForGroup(string $group, array $pairs, int $studentId): array
    {
        if ($group === 'completa') {
            return [$pairs, EnrollmentStatus::Approved, EnrollmentDetailStatus::Confirmed];
        }

        $enrollmentStatus = crc32('demo-enrollment-detail-status-'.$studentId) % 2 === 0
            ? EnrollmentStatus::Draft
            : EnrollmentStatus::Confirmed;
        $detailStatus = $enrollmentStatus === EnrollmentStatus::Draft
            ? EnrollmentDetailStatus::Draft
            : EnrollmentDetailStatus::Confirmed;

        return [$this->partialSelection($pairs, $studentId), $enrollmentStatus, $detailStatus];
    }

    /**
     * Resolve the (subject, section) pairs that actually have a section
     * created in the given period — university sections are per-subject;
     * school sections are shared per grade via the `section_subjects` pivot.
     *
     * @param  Collection<int, Subject>  $subjects
     * @return array<int, array{subject: Subject, section: Section}>
     */
    private function resolveSubjectSectionPairs(Collection $subjects, Period $period, bool $isUniversity): array
    {
        $pairs = [];

        foreach ($subjects as $subject) {
            $section = $isUniversity
                ? Section::where('subject_id', $subject->id)
                    ->where('period_id', $period->id)
                    ->orderBy('code')
                    ->first()
                : Section::whereHas('sectionSubjects', fn ($q) => $q->where('subjects.id', $subject->id))
                    ->where('period_id', $period->id)
                    ->first();

            if (! $section) {
                continue;
            }

            $pairs[] = ['subject' => $subject, 'section' => $section];
        }

        return $pairs;
    }

    /**
     * Pick a non-empty, non-complete deterministic subset of the given pairs,
     * keyed by student id so repeated seeder runs select the same subset
     * (true idempotency — `random_int` would grow the subset on every re-run).
     *
     * @param  array<int, array{subject: Subject, section: Section}>  $pairs
     * @return array<int, array{subject: Subject, section: Section}>
     */
    private function partialSelection(array $pairs, int $studentId): array
    {
        if (count($pairs) <= 1) {
            return $pairs;
        }

        usort($pairs, fn (array $a, array $b): int => $a['subject']->id <=> $b['subject']->id);

        $count = 1 + (crc32('demo-enrollment-partial-count-'.$studentId) % (count($pairs) - 1));

        return array_slice($pairs, 0, $count);
    }

    /**
     * Deterministic (but pseudo-random looking) 3-way bucket per student,
     * so the current-period enrollment status distribution stays stable
     * across repeated seeder runs on the same data.
     */
    private function enrollmentGroup(int $studentId): string
    {
        $bucket = crc32('demo-enrollment-status-'.$studentId) % 3;

        return match ($bucket) {
            0 => 'sin',
            1 => 'a_medias',
            default => 'completa',
        };
    }
}
