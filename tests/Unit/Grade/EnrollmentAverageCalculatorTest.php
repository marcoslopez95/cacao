<?php

use App\Models\Enrollment;
use App\Models\EnrollmentDetail;
use App\Models\GradeConfig;
use App\Models\GradeEntry;
use App\Models\GradeSlot;
use App\Models\Period;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Services\Grade\EnrollmentAverageCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function averageCalculator(): EnrollmentAverageCalculator
{
    return new EnrollmentAverageCalculator;
}

/**
 * Builds an enrollment with two subjects sharing a single-slot (weight 100)
 * grade config, so each subject's final grade equals its single slot value.
 */
function averageContext(): array
{
    $period = Period::factory()->active()->create();
    $student = Student::factory()->create();
    $config = GradeConfig::factory()->university()->create([
        'period_id' => $period->id,
        'passing_value' => '10.00',
    ]);
    $slot = GradeSlot::factory()->create([
        'grade_config_id' => $config->id,
        'weight' => 100,
        'is_remedial' => false,
    ]);

    $enrollment = Enrollment::factory()->create([
        'student_id' => $student->id,
        'period_id' => $period->id,
    ]);

    $sectionA = Section::factory()->create(['period_id' => $period->id]);
    $sectionB = Section::factory()->create(['period_id' => $period->id]);

    $detailA = EnrollmentDetail::factory()->create([
        'enrollment_id' => $enrollment->id,
        'subject_id' => Subject::factory()->create()->id,
        'section_id' => $sectionA->id,
    ]);
    $detailB = EnrollmentDetail::factory()->create([
        'enrollment_id' => $enrollment->id,
        'subject_id' => Subject::factory()->create()->id,
        'section_id' => $sectionB->id,
    ]);

    return compact('enrollment', 'detailA', 'detailB', 'slot');
}

function loadEnrollment(Enrollment $enrollment): Enrollment
{
    return $enrollment->load(['period', 'details.subject', 'details.gradeEntries.children']);
}

test('returns null when no subject has a definitive grade', function () {
    ['enrollment' => $enrollment] = averageContext();

    expect(averageCalculator()->calculateForEnrollment(loadEnrollment($enrollment)))->toBeNull();
});

test('returns the simple average of final grades across subjects', function () {
    ['enrollment' => $enrollment, 'detailA' => $detailA, 'detailB' => $detailB, 'slot' => $slot] = averageContext();

    GradeEntry::factory()->create([
        'enrollment_detail_id' => $detailA->id,
        'grade_slot_id' => $slot->id,
        'value' => 16,
        'is_published' => true,
    ]);
    GradeEntry::factory()->create([
        'enrollment_detail_id' => $detailB->id,
        'grade_slot_id' => $slot->id,
        'value' => 10,
        'is_published' => true,
    ]);

    // (16 + 10) / 2 = 13
    expect(averageCalculator()->calculateForEnrollment(loadEnrollment($enrollment)))->toBe('13');
});

test('averages only subjects that already have a definitive grade', function () {
    ['enrollment' => $enrollment, 'detailA' => $detailA, 'slot' => $slot] = averageContext();

    GradeEntry::factory()->create([
        'enrollment_detail_id' => $detailA->id,
        'grade_slot_id' => $slot->id,
        'value' => 16,
        'is_published' => true,
    ]);
    // detailB has no grade entries — its final grade is null and is excluded.

    expect(averageCalculator()->calculateForEnrollment(loadEnrollment($enrollment)))->toBe('16');
});
