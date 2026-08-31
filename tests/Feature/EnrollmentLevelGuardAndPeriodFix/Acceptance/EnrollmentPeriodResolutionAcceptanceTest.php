<?php

/**
 * Acceptance tests — 16-enrollment-level-guard-and-period-fix (HLZ-43)
 *
 * These tests define the behavioural contract for the feature.
 * They MUST be RED before implementation and GREEN after.
 * The implementer MUST NOT modify this file.
 *
 * Contract:
 * RF-05 — The active period is resolved by Student::educational_level:
 *         University -> Period type=Semester; Primary/Secondary -> Period type=Year.
 *         Verified with two coexisting Active periods (one Semester, one Year), reproducing the
 *         real HLZ-43 scenario ("2026-I" semester + "2025-2026" year both Active).
 * RF-06 — The period label shown in the enrollment UI uses the real name of the currently
 *         in-range Lapse when the resolved period is type Year, instead of the hardcoded
 *         "{academic_year}er trimestre" string.
 */

use App\Models\Catalogs\KinshipType;
use App\Models\Guardian;
use App\Models\Lapse;
use App\Models\Pensum;
use App\Models\Period;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

// ---------------------------------------------------------------------------
// RF-05 — period resolved by student's educational_level, among two Active periods
// ---------------------------------------------------------------------------

test('RF-05: university student resolves the Active Semester period, not the coexisting Active Year period', function () {
    // Year period created FIRST on purpose: without the RF-05 fix, Period::where('status', Active)
    // ->first() has no type filter and returns rows in insertion/id order, so it would incorrectly
    // resolve this Year period for a University student. Creating it first makes the test a
    // reliable RED before the fix instead of coincidentally passing due to insertion order.
    Period::factory()->year()->active()->create(['name' => '2025-2026']);
    $semester = Period::factory()->semester()->active()->create(['name' => '2026-I']);

    $pensum = Pensum::factory()->create();
    $student = Student::factory()->create(['current_pensum_id' => $pensum->id]); // default: University

    $this->actingAs($student->user)
        ->get('/enrollment')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('rules.period', $semester->name));

    $this->assertDatabaseHas('enrollments', [
        'student_id' => $student->id,
        'period_id' => $semester->id,
    ]);
});

test('RF-05: secondary student resolves the Active Year period, not the coexisting Active Semester period', function () {
    Period::factory()->semester()->active()->create(['name' => '2026-I']);
    $year = Period::factory()->year()->active()->create(['name' => '2025-2026']);

    $pensum = Pensum::factory()->create();
    $student = Student::factory()->secondary()->create(['current_pensum_id' => $pensum->id]);
    $guardian = Guardian::factory()->create();
    $kinship = KinshipType::firstOrCreate(['code' => 'other'], ['name' => 'Otro', 'active' => true, 'sort_order' => 99]);
    $student->guardians()->attach($guardian->id, [
        'kinship_type_id' => $kinship->id,
        'is_primary' => true,
        'is_emergency_contact' => false,
    ]);

    $this->actingAs($guardian->user)
        ->get("/enrollment?student_id={$student->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('rules.period', $year->name));

    $this->assertDatabaseHas('enrollments', [
        'student_id' => $student->id,
        'period_id' => $year->id,
    ]);
});

// ---------------------------------------------------------------------------
// RF-06 — label shows the real in-range Lapse name for Year periods
// ---------------------------------------------------------------------------

test('RF-06: enrollment rules label shows the real active Lapse name for a Year period, not the hardcoded trimester string', function () {
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

    $this->actingAs($guardian->user)
        ->get("/enrollment?student_id={$student->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('rules.trimester', $lapse->name)
            ->whereNot('rules.trimester', '3er trimestre')
        );
});
