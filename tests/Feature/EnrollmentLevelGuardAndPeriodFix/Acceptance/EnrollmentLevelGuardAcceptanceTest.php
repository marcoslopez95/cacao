<?php

/**
 * Acceptance tests — 16-enrollment-level-guard-and-period-fix (HLZ-38)
 *
 * These tests define the behavioural contract for the feature.
 * They MUST be RED before implementation and GREEN after.
 * The implementer MUST NOT modify this file.
 *
 * Contract:
 * RF-01 — GET /enrollment for a student with educational_level != University responds 403
 *         and never creates an Enrollment draft.
 * RF-02 — GET /enrollment for a University student keeps working exactly as today (regression).
 * RF-04 — GET /enrollment?student_id={id} by a guardian linked to a University student responds 403
 *         (defensive scenario — forced via factory, does not occur today with real data).
 *
 * NOTE: RF-03 (non-empty catalog for a guardian-enrolled secondary/primary student) is NOT covered
 * in this file. It originally surfaced a real contradiction between design.md and the code (see
 * git history of this comment for the full finding), which the human resolved on 2026-08-30
 * (decision: Option A — expand the design instead of narrowing RF-03). RF-03 is now covered in
 * tests/Feature/EnrollmentLevelGuardAndPeriodFix/Acceptance/EnrollmentSchoolCatalogAcceptanceTest.php,
 * exercising the expanded contract: `Subject::schoolSections(): BelongsToMany` +
 * `BuildEnrollmentCatalogAction::handle()` combining `sections` and `schoolSections`.
 */

use App\Enums\EducationalLevel;
use App\Enums\EnrollmentStatus;
use App\Models\Catalogs\KinshipType;
use App\Models\Guardian;
use App\Models\Pensum;
use App\Models\Period;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function attachGuardianToStudentForLevelGuardFix(Guardian $guardian, Student $student): void
{
    $kinship = KinshipType::firstOrCreate(
        ['code' => 'other'],
        ['name' => 'Otro', 'active' => true, 'sort_order' => 99],
    );

    $student->guardians()->attach($guardian->id, [
        'kinship_type_id' => $kinship->id,
        'is_primary' => true,
        'is_emergency_contact' => false,
    ]);
}

function makeUniversityStudentWithCatalogForLevelGuardFix(): array
{
    $period = Period::factory()->semester()->active()->create();
    $pensum = Pensum::factory()->create();
    $subject = Subject::factory()->create(['pensum_id' => $pensum->id, 'period_number' => 1]);
    $section = Section::factory()->create(['subject_id' => $subject->id, 'period_id' => $period->id, 'capacity' => 30]);
    Schedule::factory()->create(['section_id' => $section->id, 'subject_id' => $subject->id]);
    $student = Student::factory()->create([
        'current_pensum_id' => $pensum->id,
        'academic_year' => 1,
    ]);

    return compact('period', 'pensum', 'subject', 'section', 'student');
}

// ---------------------------------------------------------------------------
// RF-01 — non-university student is blocked before touching the DB
// ---------------------------------------------------------------------------

test('RF-01: secondary student self-enrolling gets 403 and no enrollment draft is created', function () {
    Period::factory()->year()->active()->create();
    $pensum = Pensum::factory()->create();
    $student = Student::factory()->secondary()->create(['current_pensum_id' => $pensum->id]);

    $this->assertDatabaseMissing('enrollments', ['student_id' => $student->id]);

    $this->actingAs($student->user)
        ->get('/enrollment')
        ->assertForbidden();

    $this->assertDatabaseMissing('enrollments', ['student_id' => $student->id]);
    $this->assertDatabaseCount('enrollments', 0);
});

test('RF-01: primary student self-enrolling gets 403 and no enrollment draft is created', function () {
    Period::factory()->year()->active()->create();
    $pensum = Pensum::factory()->create();
    $student = Student::factory()->primary()->create(['current_pensum_id' => $pensum->id]);

    $this->actingAs($student->user)
        ->get('/enrollment')
        ->assertForbidden();

    $this->assertDatabaseMissing('enrollments', ['student_id' => $student->id]);
});

// ---------------------------------------------------------------------------
// RF-02 — university student self-enrolling is unaffected (regression)
// ---------------------------------------------------------------------------

test('RF-02: university student self-enrolling keeps working exactly as before (regression)', function () {
    ['student' => $student, 'period' => $period, 'pensum' => $pensum] = makeUniversityStudentWithCatalogForLevelGuardFix();

    expect($student->educational_level)->toBe(EducationalLevel::University);

    $this->actingAs($student->user)
        ->get('/enrollment')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('enrollment/Index')
            ->has('enrollment')
            ->has('catalog', 1)
            ->has('rules')
            ->has('can')
        );

    $this->assertDatabaseHas('enrollments', [
        'student_id' => $student->id,
        'period_id' => $period->id,
        'pensum_id' => $pensum->id,
        'status' => EnrollmentStatus::Draft->value,
    ]);
});

// ---------------------------------------------------------------------------
// RF-04 — guardian cannot enroll a university-level linked student (defensive)
// ---------------------------------------------------------------------------

test('RF-04: guardian linked to a university student gets 403 on GET /enrollment (defensive scenario)', function () {
    $pensum = Pensum::factory()->create();
    $student = Student::factory()->create(['current_pensum_id' => $pensum->id]); // default: University
    $guardian = Guardian::factory()->create();
    attachGuardianToStudentForLevelGuardFix($guardian, $student);

    Period::factory()->semester()->active()->create();

    $this->actingAs($guardian->user)
        ->get("/enrollment?student_id={$student->id}")
        ->assertForbidden();

    $this->assertDatabaseMissing('enrollments', ['student_id' => $student->id]);
});

// ---------------------------------------------------------------------------
// Regression net: guardian still can enroll a linked secondary/primary student
// ---------------------------------------------------------------------------

test('guardian linked to a secondary student is still allowed (create policy) after the guard fix', function () {
    $pensum = Pensum::factory()->create();
    $student = Student::factory()->secondary()->create(['current_pensum_id' => $pensum->id]);
    $guardian = Guardian::factory()->create();
    attachGuardianToStudentForLevelGuardFix($guardian, $student);

    Period::factory()->year()->active()->create();

    $this->actingAs($guardian->user)
        ->get("/enrollment?student_id={$student->id}")
        ->assertOk();
});
