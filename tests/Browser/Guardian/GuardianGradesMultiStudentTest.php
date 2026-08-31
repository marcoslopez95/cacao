<?php

use App\Enums\EducationalLevel;
use App\Models\Catalogs\KinshipType;
use App\Models\Enrollment;
use App\Models\EnrollmentDetail;
use App\Models\GradeConfig;
use App\Models\GradeEntry;
use App\Models\GradeSlot;
use App\Models\Guardian;
use App\Models\Period;
use App\Models\Section;
use App\Models\Student;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Spatie\Permission\PermissionRegistrar;

/**
 * Browser contract for HLZ-40 + HLZ-41 (feature 18-guardian-grades-multi-student).
 *
 * UC-QA-01: representante con 2 estudiantes consulta notas del segundo mediante
 *           el selector de estudiante en /guardian/grades.
 * UC-QA-02: representante con 1 solo estudiante no ve selector — comportamiento
 *           idéntico al actual.
 *
 * UC-QA-03 y UC-QA-04 (según specs/18-guardian-grades-multi-student/qa.md) son
 * escenarios defensivos cubiertos solo como Feature test — ver
 * tests/Feature/GuardianGradesMultiStudent/Acceptance/GuardianGradesMultiStudentTest.php
 *
 * Contrato de selector asumido por este test (no especificado exactamente en
 * design.md): un elemento <select dusk="guardian-grades-student-select"> con
 * un <option :value="student.id"> por cada estudiante elegible, visible solo
 * cuando students.length > 1. Ver nota de ambigüedad en el reporte del tester.
 */
uses(DatabaseMigrations::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

function attachDuskStudentToGuardian(Student $student, Guardian $guardian, bool $isPrimary = false): void
{
    $kinship = KinshipType::firstOrCreate(
        ['code' => 'other'],
        ['name' => 'Otro', 'active' => true, 'sort_order' => 99],
    );

    $student->guardians()->attach($guardian->id, [
        'kinship_type_id' => $kinship->id,
        'is_primary' => $isPrimary,
        'is_emergency_contact' => false,
    ]);
}

/**
 * @return array{guardian: Guardian, studentA: Student, studentB: Student, subjectNameA: string, subjectNameB: string}
 */
function guardianGradesMultiStudentDuskContext(): array
{
    $guardian = Guardian::factory()->create();

    $studentA = Student::factory()->secondary()->create();
    $studentB = Student::factory()->primary()->create();

    attachDuskStudentToGuardian($studentA, $guardian, isPrimary: true);
    attachDuskStudentToGuardian($studentB, $guardian);

    $period = Period::factory()->active()->create();

    $config = GradeConfig::factory()->primarySecondary()->create();
    $slot = GradeSlot::factory()->create([
        'grade_config_id' => $config->id,
        'weight' => '100.00',
    ]);

    $sectionA = Section::factory()->create(['period_id' => $period->id]);
    $enrollmentA = Enrollment::factory()->create(['student_id' => $studentA->id, 'period_id' => $period->id]);
    $detailA = EnrollmentDetail::factory()->create(['enrollment_id' => $enrollmentA->id, 'section_id' => $sectionA->id]);
    GradeEntry::factory()->published()->create(['enrollment_detail_id' => $detailA->id, 'grade_slot_id' => $slot->id, 'value' => 12]);

    $sectionB = Section::factory()->create(['period_id' => $period->id]);
    $enrollmentB = Enrollment::factory()->create(['student_id' => $studentB->id, 'period_id' => $period->id]);
    $detailB = EnrollmentDetail::factory()->create(['enrollment_id' => $enrollmentB->id, 'section_id' => $sectionB->id]);
    GradeEntry::factory()->published()->create(['enrollment_detail_id' => $detailB->id, 'grade_slot_id' => $slot->id, 'value' => 18]);

    return [
        'guardian' => $guardian,
        'studentA' => $studentA,
        'studentB' => $studentB,
        'subjectNameA' => $detailA->subject->name,
        'subjectNameB' => $detailB->subject->name,
    ];
}

// ---------------------------------------------------------------------------
// UC-QA-01
// ---------------------------------------------------------------------------

test('UC-QA-01: representante con 2 estudiantes consulta notas del segundo mediante el selector', function () {
    $ctx = guardianGradesMultiStudentDuskContext();
    $guardian = $ctx['guardian'];

    // El fallback (sin student_id) es no determinístico entre A y B según el
    // orden natural de la relación — calculamos cuál es el "primero" real y
    // seleccionamos explícitamente el otro para probar el cambio.
    $eligible = $guardian->students()
        ->where('educational_level', '!=', EducationalLevel::University)
        ->get();
    $initial = $eligible->first();
    $other = $eligible->firstWhere('id', '!=', $initial->id);
    $otherSubjectName = $other->id === $ctx['studentA']->id ? $ctx['subjectNameA'] : $ctx['subjectNameB'];
    $initialSubjectName = $initial->id === $ctx['studentA']->id ? $ctx['subjectNameA'] : $ctx['subjectNameB'];

    $this->browse(function (Browser $browser) use ($guardian, $initial, $other, $otherSubjectName, $initialSubjectName) {
        $browser->loginAs($guardian->user)
            ->visit('/guardian/grades')
            ->waitForText('Notas de '.$initial->user->name, 10)
            ->assertSee($initialSubjectName)
            ->waitFor('[dusk="guardian-grades-student-select"]')
            ->select('[dusk="guardian-grades-student-select"]', (string) $other->id)
            ->waitForText('Notas de '.$other->user->name, 10)
            ->assertSee($otherSubjectName)
            ->assertDontSee($initialSubjectName);
    });
});

// ---------------------------------------------------------------------------
// UC-QA-02
// ---------------------------------------------------------------------------

test('UC-QA-02: representante con 1 solo estudiante no ve selector', function () {
    $guardian = Guardian::factory()->create();
    $student = Student::factory()->secondary()->create();
    attachDuskStudentToGuardian($student, $guardian, isPrimary: true);
    Period::factory()->active()->create();

    $this->browse(function (Browser $browser) use ($guardian, $student) {
        $browser->loginAs($guardian->user)
            ->visit('/guardian/grades')
            ->waitForText('Notas de '.$student->user->name, 10)
            ->assertMissing('[dusk="guardian-grades-student-select"]');
    });
});
