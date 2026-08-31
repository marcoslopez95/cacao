<?php

/**
 * Dusk acceptance tests — 16-enrollment-level-guard-and-period-fix (HLZ-38 + HLZ-43)
 *
 * Written in tester `pre` mode: the feature is not implemented yet, these tests MUST fail (RED)
 * for the reasons documented in each test. No `dusk="..."` attributes were added to any Vue
 * component — that exception does not apply in `pre` mode (design.md: components already exist,
 * but the rule is unconditional for this mode). Interactions rely on existing CSS classes (and the
 * one pre-existing `[dusk="guardian-enroll-btn"]` attribute already shipped in guardian/Dashboard.vue
 * by a previous feature).
 *
 * Covers from specs/16-enrollment-level-guard-and-period-fix/qa.md:
 * - UC-QA-01 — University student self-enrolls without changes (regression).
 * - UC-QA-02 — Secondary student cannot self-enroll (CTA hidden + 403 on direct visit).
 * - UC-QA-03 — Guardian enrolls a linked secondary student with a real (non-empty) catalog.
 * - UC-QA-05 — Enrollment header shows the real active Lapse name, not the hardcoded label.
 *
 * NOT covered here:
 * - UC-QA-04 — covered as a Feature/Acceptance test instead (forced factory scenario, not a real
 *   browser flow): see EnrollmentLevelGuardAcceptanceTest::RF-04.
 *
 * Resolved ambiguity (2026-08-30, decision: Option A — expand the design):
 * UC-QA-03 was originally left pending because BuildEnrollmentCatalogAction never resolved
 * `School`-type sections (subject_id is always null on them; subjects link only through the
 * `section_subjects` pivot). The human decided to expand the design (see design.md, "Corrección
 * al diseño (2026-08-30)") instead of narrowing the UC: add `Subject::schoolSections(): BelongsToMany`
 * and combine it with `sections` in `BuildEnrollmentCatalogAction::handle()`. The test below now
 * exercises that contract end-to-end through the browser and is expected to be RED because neither
 * change exists yet — the catalog stays empty and nothing is clickable.
 */

use App\Models\Catalogs\KinshipType;
use App\Models\Guardian;
use App\Models\Lapse;
use App\Models\Pensum;
use App\Models\Period;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

uses(DatabaseMigrations::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function attachGuardianForLevelGuardDusk(Guardian $guardian, Student $student): void
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

// ---------------------------------------------------------------------------
// UC-QA-01 — University student self-enrolls without changes (regression)
// ---------------------------------------------------------------------------
// Expected pre-fix status: this test SHOULD already pass before the implementer's fix — RF-02
// requires the university self-enroll flow to keep working exactly as today. It stays in this
// suite as a regression guard, not as a new-behaviour RED test.

test('university student can select a section and confirm the enrollment (UC-QA-01, regression)', function () {
    $period = Period::factory()->semester()->active()->create();
    $pensum = Pensum::factory()->create();
    $subject = Subject::factory()->create([
        'pensum_id' => $pensum->id,
        'period_number' => 1,
        'credits_uc' => 12,
    ]);
    $section = Section::factory()->create([
        'subject_id' => $subject->id,
        'period_id' => $period->id,
        'capacity' => 30,
    ]);
    Schedule::factory()->create(['section_id' => $section->id, 'subject_id' => $subject->id]);
    $student = Student::factory()->create([
        'current_pensum_id' => $pensum->id,
        'academic_year' => 1,
    ]);

    $this->browse(function ($browser) use ($student) {
        $browser->loginAs($student->user)
            ->visit('/enrollment')
            ->waitForText('Inscripción de materias', 10)
            ->click('.enr-mat-trigger')
            ->waitFor('.enr-sec-card', 5)
            ->click('.enr-sec-card-cta button')
            ->waitForText('Seleccionada', 5)
            ->click('.enr-side-actions button')
            ->waitForText('Inscripción confirmada', 10);
    });

    $this->assertDatabaseHas('enrollments', [
        'student_id' => $student->id,
        'status' => 'confirmed',
    ]);
    $this->assertDatabaseHas('enrollment_details', [
        'subject_id' => $subject->id,
        'section_id' => $section->id,
        'status' => 'confirmed',
    ]);
});

// ---------------------------------------------------------------------------
// UC-QA-02 — Secondary student cannot self-enroll
// ---------------------------------------------------------------------------

test('secondary student dashboard hides the enrollment CTA and direct visit to /enrollment is forbidden (UC-QA-02)', function () {
    $pensum = Pensum::factory()->create();
    $student = Student::factory()->secondary()->create(['current_pensum_id' => $pensum->id]);

    $this->browse(function ($browser) use ($student) {
        $browser->loginAs($student->user)
            ->visit('/student/dashboard')
            ->waitForText('Bienvenido', 10)
            ->assertDontSee('Ir a inscripciones')
            ->assertSee('Tu representante debe inscribirte.')
            ->visit('/enrollment')
            ->pause(2000)
            ->assertSee('no puedes pasar');
    });

    $this->assertDatabaseMissing('enrollments', ['student_id' => $student->id]);
});

// ---------------------------------------------------------------------------
// UC-QA-03 — Guardian enrolls a linked secondary student with a real catalog
// ---------------------------------------------------------------------------

test('guardian enrolls a linked secondary student with a non-empty catalog of real School sections (UC-QA-03)', function () {
    $year = Period::factory()->year()->active()->create(['name' => '2025-2026']);

    $pensum = Pensum::factory()->create([
        'period_type' => 'year',
        'total_periods' => 5,
    ]);

    $grade = 4;

    $subject = Subject::factory()->create([
        'pensum_id' => $pensum->id,
        'period_number' => $grade,
        'name' => 'Matemática',
        'credits_uc' => 12,
    ]);

    $section = Section::factory()->forPensumAndGrade($pensum, $grade, 'A')->create([
        'period_id' => $year->id,
        'capacity' => 30,
    ]);

    DB::table('section_subjects')->insert([
        'section_id' => $section->id,
        'subject_id' => $subject->id,
    ]);

    $student = Student::factory()->secondary()->create([
        'current_pensum_id' => $pensum->id,
        'academic_year' => $grade,
    ]);
    $guardian = Guardian::factory()->create();
    attachGuardianForLevelGuardDusk($guardian, $student);

    $this->browse(function ($browser) use ($guardian, $student, $subject) {
        $browser->loginAs($guardian->user)
            ->visit('/guardian/dashboard')
            ->waitForText($student->user->name, 10)
            ->click('[dusk="guardian-enroll-btn"]')
            ->waitForText('Inscripción de materias', 10)
            ->assertDontSee('Sin resultados')
            ->assertSeeIn('.enr-list', $subject->name)
            ->click('.enr-mat-trigger')
            ->waitFor('.enr-sec-card', 5)
            ->click('.enr-sec-card-cta button')
            ->waitForText('Seleccionada', 5)
            ->click('.enr-side-actions button')
            ->waitForText('Inscripción confirmada', 10);
    });

    $this->assertDatabaseHas('enrollments', [
        'student_id' => $student->id,
        'period_id' => $year->id,
        'status' => 'confirmed',
    ]);
    $this->assertDatabaseHas('enrollment_details', [
        'subject_id' => $subject->id,
        'section_id' => $section->id,
        'status' => 'confirmed',
    ]);
});

// ---------------------------------------------------------------------------
// UC-QA-05 — Enrollment header shows the real active Lapse name
// ---------------------------------------------------------------------------
// Uses the guardian path (not direct student self-login) because after RF-01 a secondary student
// can no longer self-enroll; the guardian-enroll flow is the only one that remains reachable for
// a secondary student and lets us verify the header label independently of the (separately
// flagged) empty-catalog issue.

test('enrollment header shows the real active Lapse name instead of the hardcoded trimester label (UC-QA-05)', function () {
    $year = Period::factory()->year()->active()->create([
        'name' => '2025-2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);
    $lapse = Lapse::factory()->create([
        'period_id' => $year->id,
        'number' => 2,
        'name' => 'Segundo Lapso',
        'start_date' => '2026-08-01',
        'end_date' => '2026-10-31',
    ]);

    $pensum = Pensum::factory()->create();
    $student = Student::factory()->secondary()->create([
        'current_pensum_id' => $pensum->id,
        'academic_year' => 3,
    ]);
    $guardian = Guardian::factory()->create();
    attachGuardianForLevelGuardDusk($guardian, $student);

    $this->browse(function ($browser) use ($guardian, $student, $lapse) {
        // `.enr-page-eyebrow` has `text-transform: uppercase` in CSS — Selenium's
        // getText() returns the browser-rendered (already-transformed) text, not the
        // raw DOM text, so the assertion must compare against the uppercased string.
        $browser->loginAs($guardian->user)
            ->visit('/enrollment?student_id='.$student->id)
            ->waitForText('Inscripción de materias', 10)
            ->assertSeeIn('.enr-page-eyebrow', mb_strtoupper($lapse->name))
            ->assertDontSeeIn('.enr-page-eyebrow', mb_strtoupper('3er trimestre'));
    });
});
