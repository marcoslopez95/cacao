<?php

use App\Models\Enrollment;
use App\Models\EnrollmentDetail;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Services\Enrollment\PrerequisiteValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// canTake — no prerequisites
// ---------------------------------------------------------------------------

test('validator passes when subject has no prerequisites', function () {
    $subject = Subject::factory()->create();
    $student = Student::factory()->create();

    $validator = new PrerequisiteValidator;

    expect($validator->canTake($student, $subject))->toBeTrue();
});

// ---------------------------------------------------------------------------
// canTake — with prerequisites
// ---------------------------------------------------------------------------

test('validator fails when subject has prerequisites and student has no approved enrollment', function () {
    $prereq = Subject::factory()->create();
    $subject = Subject::factory()->create();
    $subject->prerequisites()->attach($prereq);
    $student = Student::factory()->create();

    $validator = new PrerequisiteValidator;

    expect($validator->canTake($student, $subject))->toBeFalse();
});

test('validator passes when student has approved enrollment detail for all prerequisites', function () {
    $prereq = Subject::factory()->create();
    $subject = Subject::factory()->create();
    $subject->prerequisites()->attach($prereq);
    $student = Student::factory()->create();

    $enrollment = Enrollment::factory()->approved()->create(['student_id' => $student->id]);
    $section = Section::factory()->create(['subject_id' => $prereq->id]);
    EnrollmentDetail::factory()->confirmed()->create([
        'enrollment_id' => $enrollment->id,
        'subject_id' => $prereq->id,
        'section_id' => $section->id,
    ]);

    $validator = new PrerequisiteValidator;

    expect($validator->canTake($student, $subject))->toBeTrue();
});

test('validator fails when enrollment is not approved', function () {
    $prereq = Subject::factory()->create();
    $subject = Subject::factory()->create();
    $subject->prerequisites()->attach($prereq);
    $student = Student::factory()->create();

    $enrollment = Enrollment::factory()->confirmed()->create(['student_id' => $student->id]);
    $section = Section::factory()->create(['subject_id' => $prereq->id]);
    EnrollmentDetail::factory()->confirmed()->create([
        'enrollment_id' => $enrollment->id,
        'subject_id' => $prereq->id,
        'section_id' => $section->id,
    ]);

    $validator = new PrerequisiteValidator;

    expect($validator->canTake($student, $subject))->toBeFalse();
});

// ---------------------------------------------------------------------------
// getMissingPrerequisites — no prerequisites
// ---------------------------------------------------------------------------

test('get missing prerequisites returns empty array when no prerequisites', function () {
    $subject = Subject::factory()->create();
    $student = Student::factory()->create();

    $validator = new PrerequisiteValidator;

    expect($validator->getMissingPrerequisites($student, $subject))->toBeEmpty();
});

// ---------------------------------------------------------------------------
// getMissingPrerequisites — with prerequisites
// ---------------------------------------------------------------------------

test('get missing prerequisites returns all when student passed none', function () {
    $prereq1 = Subject::factory()->create();
    $prereq2 = Subject::factory()->create();
    $subject = Subject::factory()->create();
    $subject->prerequisites()->attach([$prereq1->id, $prereq2->id]);
    $student = Student::factory()->create();

    $validator = new PrerequisiteValidator;

    $missing = $validator->getMissingPrerequisites($student, $subject);

    expect($missing)->toHaveCount(2)
        ->and($missing)->toContain($prereq1->id)
        ->and($missing)->toContain($prereq2->id);
});

test('get missing prerequisites returns only subjects not yet passed', function () {
    $prereq1 = Subject::factory()->create();
    $prereq2 = Subject::factory()->create();
    $subject = Subject::factory()->create();
    $subject->prerequisites()->attach([$prereq1->id, $prereq2->id]);
    $student = Student::factory()->create();

    // Student passed prereq1 only
    $enrollment = Enrollment::factory()->approved()->create(['student_id' => $student->id]);
    $section = Section::factory()->create(['subject_id' => $prereq1->id]);
    EnrollmentDetail::factory()->confirmed()->create([
        'enrollment_id' => $enrollment->id,
        'subject_id' => $prereq1->id,
        'section_id' => $section->id,
    ]);

    $validator = new PrerequisiteValidator;

    $missing = $validator->getMissingPrerequisites($student, $subject);

    expect($missing)->toHaveCount(1)
        ->and(array_values($missing))->toContain($prereq2->id);
});
