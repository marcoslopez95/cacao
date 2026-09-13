<?php

/**
 * Acceptance tests — 22-qa-round3-followup-fixes (HLZ-43, ampliación)
 *
 * These tests define the behavioural contract for RF-01 of this feature.
 * They MUST be RED before implementation and GREEN after.
 * The implementer MUST NOT modify this file.
 *
 * Contract:
 * RF-01 — EnrollmentController::buildRules() resolves $periodLabel by $period->type: for
 *         Semester, it uses the real period name (never the hardcoded
 *         "{academic_year}er trimestre" string built solely from $student->academic_year); for
 *         Year, it keeps resolving the active Lapse — that regression path is already covered by
 *         feature 16-enrollment-level-guard-and-period-fix's own acceptance suite
 *         (tests/Feature/EnrollmentLevelGuardAndPeriodFix/Acceptance/EnrollmentPeriodResolutionAcceptanceTest.php)
 *         and is not duplicated here.
 *
 * Covers specs/22-qa-round3-followup-fixes/qa.md UC-QA-01 (backend half). The rendered-copy half
 * of UC-QA-01/UC-QA-02 (header + help text actually shown in the browser) is covered by
 * tests/Browser/Enrollment/QaRound3PeriodLabelTest.php.
 */

use App\Models\Pensum;
use App\Models\Period;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

test('RF-01: university student with an active Semester period gets the real period name as label, never "Xer trimestre"', function () {
    $semester = Period::factory()->semester()->active()->create(['name' => '2026-I']);
    $pensum = Pensum::factory()->create();
    // academic_year populated is exactly what makes the pre-fix bug fire: buildRules() builds
    // "{academic_year}er trimestre" from this field alone, with no condition on $period->type.
    $student = Student::factory()->create([
        'current_pensum_id' => $pensum->id,
        'academic_year' => 3,
    ]);

    $this->actingAs($student->user)
        ->get('/enrollment')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('rules.trimester', $semester->name)
            ->whereNot('rules.trimester', '3er trimestre')
        );
});

test('RF-01: university student with an active Semester period and no academic_year still gets the real period name as label', function () {
    $semester = Period::factory()->semester()->active()->create(['name' => '2026-II']);
    $pensum = Pensum::factory()->create();
    $student = Student::factory()->create([
        'current_pensum_id' => $pensum->id,
        'academic_year' => null,
    ]);

    $this->actingAs($student->user)
        ->get('/enrollment')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('rules.trimester', $semester->name)
        );
});
