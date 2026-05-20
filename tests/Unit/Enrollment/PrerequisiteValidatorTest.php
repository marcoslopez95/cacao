<?php

use App\Models\Student;
use App\Models\Subject;
use App\Services\Enrollment\PrerequisiteValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

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
// canTake — with prerequisites, grades mocked
// ---------------------------------------------------------------------------

test('validator fails when subject has prerequisites and student has no grades', function () {
    $prereq = Subject::factory()->create();
    $subject = Subject::factory()->create();
    $subject->prerequisites()->attach($prereq);
    $student = Student::factory()->create();

    $validator = new PrerequisiteValidator;

    // grades table does not exist yet — mock DB::table('grades') chain
    DB::shouldReceive('table')
        ->with('grades')
        ->andReturnSelf();
    DB::shouldReceive('where')
        ->andReturnSelf();
    DB::shouldReceive('whereIn')
        ->andReturnSelf();
    DB::shouldReceive('pluck')
        ->andReturn(collect([]));

    expect($validator->canTake($student, $subject))->toBeFalse();
});

test('validator passes when student has passed all prerequisites', function () {
    $prereq = Subject::factory()->create();
    $subject = Subject::factory()->create();
    $subject->prerequisites()->attach($prereq);
    $student = Student::factory()->create();

    $validator = new PrerequisiteValidator;

    // grades table does not exist yet — mock DB::table('grades') chain
    DB::shouldReceive('table')
        ->with('grades')
        ->andReturnSelf();
    DB::shouldReceive('where')
        ->andReturnSelf();
    DB::shouldReceive('whereIn')
        ->andReturnSelf();
    DB::shouldReceive('pluck')
        ->andReturn(collect([$prereq->id]));

    expect($validator->canTake($student, $subject))->toBeTrue();
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
// getMissingPrerequisites — with prerequisites, grades mocked
// ---------------------------------------------------------------------------

test('get missing prerequisites returns all when student passed none', function () {
    $prereq1 = Subject::factory()->create();
    $prereq2 = Subject::factory()->create();
    $subject = Subject::factory()->create();
    $subject->prerequisites()->attach([$prereq1->id, $prereq2->id]);
    $student = Student::factory()->create();

    $validator = new PrerequisiteValidator;

    DB::shouldReceive('table')
        ->with('grades')
        ->andReturnSelf();
    DB::shouldReceive('where')
        ->andReturnSelf();
    DB::shouldReceive('whereIn')
        ->andReturnSelf();
    DB::shouldReceive('pluck')
        ->andReturn(collect([]));

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

    $validator = new PrerequisiteValidator;

    // Student passed prereq1 only
    DB::shouldReceive('table')
        ->with('grades')
        ->andReturnSelf();
    DB::shouldReceive('where')
        ->andReturnSelf();
    DB::shouldReceive('whereIn')
        ->andReturnSelf();
    DB::shouldReceive('pluck')
        ->andReturn(collect([$prereq1->id]));

    $missing = $validator->getMissingPrerequisites($student, $subject);

    expect($missing)->toHaveCount(1)
        ->and(array_values($missing))->toContain($prereq2->id);
});
