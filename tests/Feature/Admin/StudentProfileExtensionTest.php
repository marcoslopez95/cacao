<?php

use App\Models\Catalogs\AcademicStatus;
use App\Models\Student;
use Database\Seeders\Catalogs\AcademicCatalogsSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    (new AcademicCatalogsSeeder)->run();
});

test('cumulative_gpa rejects values below 0', function () {
    $student = Student::factory()->create();

    expect(fn () => DB::statement(
        'UPDATE students SET cumulative_gpa = ? WHERE id = ?',
        [-1.0, $student->id]
    ))->toThrow(QueryException::class);
});

test('cumulative_gpa rejects values above 20', function () {
    $student = Student::factory()->create();

    expect(fn () => DB::statement(
        'UPDATE students SET cumulative_gpa = ? WHERE id = ?',
        [20.01, $student->id]
    ))->toThrow(QueryException::class);
});

test('cumulative_gpa accepts valid values', function () {
    $student = Student::factory()->create();

    DB::statement(
        'UPDATE students SET cumulative_gpa = ? WHERE id = ?',
        [15.50, $student->id]
    );

    expect($student->fresh()->cumulative_gpa)->toBe('15.50');
});

test('academic_status_id accepts valid FK', function () {
    $student = Student::factory()->create();
    $statusId = AcademicStatus::first()->id;

    $student->update(['academic_status_id' => $statusId]);

    expect($student->fresh()->academic_status_id)->toBe($statusId);
});
