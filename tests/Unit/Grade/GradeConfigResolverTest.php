<?php

use App\Enums\GradeLevel;
use App\Models\Enrollment;
use App\Models\EnrollmentDetail;
use App\Models\GradeConfig;
use App\Models\Period;
use App\Models\Student;
use App\Services\Grade\GradeConfigResolver;

function gradeConfigResolver(): GradeConfigResolver
{
    return new GradeConfigResolver;
}

test('resolves the primary/secondary config for a secondary student', function () {
    $period = Period::factory()->create();
    $config = GradeConfig::factory()->primarySecondary()->create([
        'period_id' => $period->id,
    ]);

    $student = Student::factory()->secondary()->create();
    $enrollment = Enrollment::factory()->create([
        'student_id' => $student->id,
        'period_id' => $period->id,
    ]);
    $detail = EnrollmentDetail::factory()->create([
        'enrollment_id' => $enrollment->id,
    ]);

    $resolved = gradeConfigResolver()->resolveForDetail($detail);

    expect($resolved)->not->toBeNull();
    expect($resolved->id)->toBe($config->id);
    expect($resolved->level)->toBe(GradeLevel::PrimarySecondary);
});

test('resolves the primary/secondary config for a primary student', function () {
    $period = Period::factory()->create();
    $config = GradeConfig::factory()->primarySecondary()->create([
        'period_id' => $period->id,
    ]);

    $student = Student::factory()->primary()->create();
    $enrollment = Enrollment::factory()->create([
        'student_id' => $student->id,
        'period_id' => $period->id,
    ]);
    $detail = EnrollmentDetail::factory()->create([
        'enrollment_id' => $enrollment->id,
    ]);

    $resolved = gradeConfigResolver()->resolveForDetail($detail);

    expect($resolved)->not->toBeNull();
    expect($resolved->id)->toBe($config->id);
});

test('resolves the university config for a university student', function () {
    $period = Period::factory()->create();
    $config = GradeConfig::factory()->university()->create([
        'period_id' => $period->id,
    ]);

    $student = Student::factory()->create();
    $enrollment = Enrollment::factory()->create([
        'student_id' => $student->id,
        'period_id' => $period->id,
    ]);
    $detail = EnrollmentDetail::factory()->create([
        'enrollment_id' => $enrollment->id,
    ]);

    $resolved = gradeConfigResolver()->resolveForDetail($detail);

    expect($resolved)->not->toBeNull();
    expect($resolved->id)->toBe($config->id);
});
