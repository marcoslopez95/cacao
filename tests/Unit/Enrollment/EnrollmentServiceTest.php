<?php

use App\Enums\EnrollmentDetailStatus;
use App\Models\Enrollment;
use App\Models\EnrollmentDetail;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Services\Enrollment\EnrollmentCacheManager;
use App\Services\Enrollment\EnrollmentService;
use App\Services\Enrollment\PrerequisiteValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeService(): EnrollmentService
{
    return new EnrollmentService(new PrerequisiteValidator, new EnrollmentCacheManager);
}

test('calculate enrolled credits sums confirmed details only', function () {
    $student = Student::factory()->create();
    $subject1 = Subject::factory()->create(['credits_uc' => 3]);
    $subject2 = Subject::factory()->create(['credits_uc' => 4]);
    $section1 = Section::factory()->create(['subject_id' => $subject1->id]);
    $section2 = Section::factory()->create(['subject_id' => $subject2->id]);

    $enrollment = Enrollment::factory()->create(['student_id' => $student->id]);

    EnrollmentDetail::factory()->create([
        'enrollment_id' => $enrollment->id,
        'subject_id' => $subject1->id,
        'section_id' => $section1->id,
        'status' => EnrollmentDetailStatus::Confirmed,
    ]);

    EnrollmentDetail::factory()->create([
        'enrollment_id' => $enrollment->id,
        'subject_id' => $subject2->id,
        'section_id' => $section2->id,
        'status' => EnrollmentDetailStatus::Draft,  // draft — should NOT count
    ]);

    expect(makeService()->calculateEnrolledCredits($enrollment))->toBe(3);
});

test('has quota returns true when section has capacity', function () {
    $subject = Subject::factory()->create();
    $section = Section::factory()->create(['subject_id' => $subject->id, 'capacity' => 30]);

    expect(makeService()->hasQuota($section))->toBeTrue();
});

test('has quota returns false when section is full', function () {
    $subject = Subject::factory()->create();
    $section = Section::factory()->create(['subject_id' => $subject->id, 'capacity' => 1]);
    $enrollment = Enrollment::factory()->create();

    EnrollmentDetail::factory()->create([
        'enrollment_id' => $enrollment->id,
        'subject_id' => $subject->id,
        'section_id' => $section->id,
        'status' => EnrollmentDetailStatus::Confirmed,
    ]);

    // Invalidate the cache so quota is recalculated from DB
    (new EnrollmentCacheManager)->invalidateQuota($section);

    expect(makeService()->hasQuota($section))->toBeFalse();
});

test('is already enrolled returns true when subject exists in enrollment', function () {
    $subject = Subject::factory()->create();
    $section = Section::factory()->create(['subject_id' => $subject->id]);
    $enrollment = Enrollment::factory()->create();

    EnrollmentDetail::factory()->create([
        'enrollment_id' => $enrollment->id,
        'subject_id' => $subject->id,
        'section_id' => $section->id,
    ]);

    expect(makeService()->isAlreadyEnrolled($enrollment, $subject))->toBeTrue();
});

test('is already enrolled returns false when subject not in enrollment', function () {
    $subject = Subject::factory()->create();
    $enrollment = Enrollment::factory()->create();

    expect(makeService()->isAlreadyEnrolled($enrollment, $subject))->toBeFalse();
});
