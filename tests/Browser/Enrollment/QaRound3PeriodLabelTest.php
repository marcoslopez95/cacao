<?php

/**
 * Dusk acceptance tests — 22-qa-round3-followup-fixes (HLZ-43, ampliación)
 *
 * Written in tester `pre` mode: the fix is not implemented yet, both tests below MUST fail (RED)
 * against current code, for the reasons documented inline. No `dusk="..."` attributes were added
 * to enrollment/Index.vue — interactions rely on CSS classes already shipped by feature
 * 16-enrollment-level-guard-and-period-fix (`.enr-page-eyebrow`, `.enr-page-sub`).
 *
 * Covers specs/22-qa-round3-followup-fixes/qa.md:
 * - UC-QA-01 — University student sees the real Semester period name, never "Xer trimestre".
 * - UC-QA-02 — Secondary student (via guardian) still sees the real active Lapse name and the
 *   help copy no longer hardcodes "este trimestre" (regression guard for the fix already shipped
 *   by feature 16 — must not break here).
 */

use App\Models\Catalogs\KinshipType;
use App\Models\Guardian;
use App\Models\Lapse;
use App\Models\Pensum;
use App\Models\Period;
use App\Models\Student;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Spatie\Permission\PermissionRegistrar;

uses(DatabaseMigrations::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

// ---------------------------------------------------------------------------
// UC-QA-01 — University student sees the real Semester period label
// ---------------------------------------------------------------------------
// Pre-fix: buildRules() sets $periodLabel from $student->academic_year alone (no condition on
// $period->type), so with academic_year = 3 the eyebrow shows "... · 3ER TRIMESTRE" for a
// Semester period — wrong. The `assertDontSeeIn` below is what actually fails pre-fix; the
// `assertSeeIn` for the period name alone would already pass today since "Período {{ period }}"
// is unaffected by this bug.

test('university student sees the real Semester period name in the header, never "Xer trimestre" (UC-QA-01)', function () {
    $semester = Period::factory()->semester()->active()->create(['name' => '2026-I']);
    $pensum = Pensum::factory()->create();
    $student = Student::factory()->create([
        'current_pensum_id' => $pensum->id,
        'academic_year' => 3,
    ]);

    $this->browse(function (Browser $browser) use ($student, $semester) {
        // `.enr-page-eyebrow` has `text-transform: uppercase` in CSS — Selenium's getText()
        // returns the browser-rendered (already-transformed) text, not the raw DOM text.
        $browser->loginAs($student->user)
            ->visit('/enrollment')
            ->waitForText('Inscripción de materias', 10)
            ->assertSeeIn('.enr-page-eyebrow', mb_strtoupper($semester->name))
            ->assertDontSeeIn('.enr-page-eyebrow', mb_strtoupper('3er trimestre'))
            ->assertDontSeeIn('.enr-page-sub', 'este trimestre');
    });
});

// ---------------------------------------------------------------------------
// UC-QA-02 — Secondary student (via guardian) still sees the real Lapse name (regression)
// ---------------------------------------------------------------------------
// Must keep passing after the HLZ-43 ampliación fix — guards against the Semester-label
// condition breaking the pre-existing Year/Lapse resolution shipped by feature 16.

test('secondary student (via guardian) still sees the real active Lapse name in the header, not the hardcoded trimester copy (UC-QA-02, regression)', function () {
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
    $kinship = KinshipType::firstOrCreate(['code' => 'other'], ['name' => 'Otro', 'active' => true, 'sort_order' => 99]);
    $student->guardians()->attach($guardian->id, [
        'kinship_type_id' => $kinship->id,
        'is_primary' => true,
        'is_emergency_contact' => false,
    ]);

    $this->browse(function (Browser $browser) use ($guardian, $student, $lapse) {
        $browser->loginAs($guardian->user)
            ->visit('/enrollment?student_id='.$student->id)
            ->waitForText('Inscripción de materias', 10)
            ->assertSeeIn('.enr-page-eyebrow', mb_strtoupper($lapse->name))
            ->assertDontSeeIn('.enr-page-eyebrow', mb_strtoupper('3er trimestre'))
            ->assertDontSeeIn('.enr-page-sub', 'este trimestre');
    });
});
