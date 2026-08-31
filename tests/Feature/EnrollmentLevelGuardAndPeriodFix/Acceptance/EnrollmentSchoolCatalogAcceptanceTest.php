<?php

/**
 * Acceptance tests — 16-enrollment-level-guard-and-period-fix (HLZ-43, RF-03)
 *
 * These tests define the behavioural contract for the feature.
 * They MUST be RED before implementation and GREEN after.
 * The implementer MUST NOT modify this file.
 *
 * Contract:
 * RF-03 — GET /enrollment?student_id={id} by a guardian for a linked `secondary`/`primary`
 *         student resolves the active `Year` period AND returns a non-empty catalog built from
 *         real `School`-type sections.
 *
 * Resolved ambiguity (2026-08-30, decision: Option A — expand the design):
 * The tester in `pre` mode originally found that BuildEnrollmentCatalogAction only resolves
 * sections via Subject::sections() (HasMany, sections.subject_id) — which is always null for
 * `School`-type sections (they link subjects only through the `section_subjects` pivot table).
 * The human decided to expand the design instead of narrowing RF-03: `design.md`
 * ("Corrección al diseño (2026-08-30)") now specifies `Subject::schoolSections(): BelongsToMany`
 * (inverse of `Section::sectionSubjects()`) plus a `BuildEnrollmentCatalogAction::handle()` change
 * that loads and concatenates both `sections` (direct FK, University) and `schoolSections`
 * (pivot, School), both filtered by `period_id`. This test asserts that contract directly. It is
 * expected to be RED right now because neither `Subject::schoolSections()` nor the
 * `BuildEnrollmentCatalogAction` change exist yet.
 */

use App\Models\Catalogs\KinshipType;
use App\Models\Guardian;
use App\Models\Pensum;
use App\Models\Period;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

// ---------------------------------------------------------------------------
// Helper — builds a real School-level scenario: Year period, School pensum,
// one grade-level Section (subject_id null, per the real data model) linked
// to its subjects only via the section_subjects pivot table.
// ---------------------------------------------------------------------------

function makeSecondaryStudentWithSchoolSectionsForCatalogFix(): array
{
    $year = Period::factory()->year()->active()->create(['name' => '2025-2026']);

    $pensum = Pensum::factory()->create([
        'period_type' => 'year',
        'total_periods' => 5,
    ]);

    $grade = 4;

    $mathSubject = Subject::factory()->create([
        'pensum_id' => $pensum->id,
        'period_number' => $grade,
        'name' => 'Matemática',
    ]);
    $spanishSubject = Subject::factory()->create([
        'pensum_id' => $pensum->id,
        'period_number' => $grade,
        'name' => 'Castellano',
    ]);

    $section = Section::factory()->forPensumAndGrade($pensum, $grade, 'A')->create([
        'period_id' => $year->id,
        'capacity' => 30,
    ]);

    DB::table('section_subjects')->insert([
        ['section_id' => $section->id, 'subject_id' => $mathSubject->id],
        ['section_id' => $section->id, 'subject_id' => $spanishSubject->id],
    ]);

    $student = Student::factory()->secondary()->create([
        'current_pensum_id' => $pensum->id,
        'academic_year' => $grade,
    ]);

    $guardian = Guardian::factory()->create();
    $kinship = KinshipType::firstOrCreate(['code' => 'other'], ['name' => 'Otro', 'active' => true, 'sort_order' => 99]);
    $student->guardians()->attach($guardian->id, [
        'kinship_type_id' => $kinship->id,
        'is_primary' => true,
        'is_emergency_contact' => false,
    ]);

    return compact('year', 'pensum', 'grade', 'mathSubject', 'spanishSubject', 'section', 'student', 'guardian');
}

// ---------------------------------------------------------------------------
// RF-03 — guardian gets a non-empty catalog for a linked secondary student
// ---------------------------------------------------------------------------

test('RF-03: guardian enrolling a linked secondary student gets a non-empty catalog built from real School sections', function () {
    [
        'year' => $year,
        'mathSubject' => $mathSubject,
        'spanishSubject' => $spanishSubject,
        'section' => $section,
        'student' => $student,
        'guardian' => $guardian,
    ] = makeSecondaryStudentWithSchoolSectionsForCatalogFix();

    $this->actingAs($guardian->user)
        ->get("/enrollment?student_id={$student->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('enrollment/Index')
            ->where('rules.period', $year->name)
            ->has('catalog', 2)
            ->has('catalog.0.sections', 1)
            ->where('catalog.0.sections.0.id', $section->id)
        );

    $this->assertDatabaseHas('enrollments', [
        'student_id' => $student->id,
        'period_id' => $year->id,
    ]);
});

test('RF-03: catalog subjects for the secondary student both point at the same real School section', function () {
    [
        'section' => $section,
        'student' => $student,
        'guardian' => $guardian,
    ] = makeSecondaryStudentWithSchoolSectionsForCatalogFix();

    $this->actingAs($guardian->user)
        ->get("/enrollment?student_id={$student->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('catalog.0.sections.0.id', $section->id)
            ->where('catalog.1.sections.0.id', $section->id)
        );
});
