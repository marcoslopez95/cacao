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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

/**
 * Acceptance contract for HLZ-40 + HLZ-41 (feature 18-guardian-grades-multi-student).
 *
 * RF-01: GET /guardian/grades?student_id={id} muestra las notas del estudiante {id}
 *        cuando está vinculado al representante autenticado.
 * RF-02: GET /guardian/grades sin student_id mantiene el fallback al primer
 *        estudiante vinculado (compatibilidad con el comportamiento actual).
 * RF-03: GET /guardian/grades?student_id={id} con un {id} no vinculado responde 403.
 * RF-04: contrato de backend para el selector — la prop `students` expone la lista
 *        de estudiantes elegibles (0, 1 o 2+). El renderizado condicional del
 *        selector en sí es responsabilidad del componente Vue y se cubre con Dusk
 *        (ver tests/Browser/Guardian/GuardianGradesMultiStudentTest.php).
 * RF-05: ningún estudiante `university` aparece en las consultas de
 *        Guardian\GradeController ni Guardian\DashboardController, ni siquiera
 *        vía un vínculo forzado en `student_guardians`.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function attachStudentToGuardian(Student $student, Guardian $guardian, bool $isPrimary = false): void
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
 * @return array{guardian: Guardian, studentA: Student, studentB: Student, period: Period, subjectNameA: string, subjectNameB: string}
 */
function guardianMultiStudentContext(): array
{
    $guardian = Guardian::factory()->create();

    $studentA = Student::factory()->secondary()->create();
    $studentB = Student::factory()->primary()->create();

    attachStudentToGuardian($studentA, $guardian, isPrimary: true);
    attachStudentToGuardian($studentB, $guardian);

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
        'period' => $period,
        'subjectNameA' => $detailA->subject->name,
        'subjectNameB' => $detailB->subject->name,
    ];
}

// ---------------------------------------------------------------------------
// RF-01 — student_id explícito muestra las notas de ese estudiante
// ---------------------------------------------------------------------------

test('RF-01: student_id de un estudiante vinculado muestra sus notas, no las del primero', function () {
    $ctx = guardianMultiStudentContext();

    $this->actingAs($ctx['guardian']->user)
        ->get(route('guardian.grades.index', ['student_id' => $ctx['studentB']->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('guardian/Grades/Index')
            ->where('student_name', $ctx['studentB']->user->name)
            ->where('student_id', $ctx['studentB']->id)
            ->where('grades.subjects.0.slots.0.value', '18.00')
        );
});

test('RF-01: student_id del otro hermano también funciona (no queda fijo al primero)', function () {
    $ctx = guardianMultiStudentContext();

    $this->actingAs($ctx['guardian']->user)
        ->get(route('guardian.grades.index', ['student_id' => $ctx['studentA']->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('student_name', $ctx['studentA']->user->name)
            ->where('student_id', $ctx['studentA']->id)
            ->where('grades.subjects.0.slots.0.value', '12.00')
        );
});

// ---------------------------------------------------------------------------
// RF-02 — sin student_id, fallback al primer estudiante vinculado
// ---------------------------------------------------------------------------

test('RF-02: sin student_id, mantiene el fallback al primer estudiante vinculado', function () {
    $ctx = guardianMultiStudentContext();

    $expected = $ctx['guardian']->students()
        ->where('educational_level', '!=', EducationalLevel::University)
        ->first();

    $this->actingAs($ctx['guardian']->user)
        ->get(route('guardian.grades.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('student_name', $expected->user->name)
            ->where('student_id', $expected->id)
        );
});

// ---------------------------------------------------------------------------
// RF-03 — student_id ajeno responde 403
// ---------------------------------------------------------------------------

test('RF-03: student_id no vinculado al representante autenticado responde 403', function () {
    $ctx = guardianMultiStudentContext();
    $ajeno = Student::factory()->secondary()->create();

    $this->actingAs($ctx['guardian']->user)
        ->get(route('guardian.grades.index', ['student_id' => $ajeno->id]))
        ->assertForbidden();
});

test('RF-03: student_id de un estudiante que no existe responde 403', function () {
    $ctx = guardianMultiStudentContext();

    $this->actingAs($ctx['guardian']->user)
        ->get(route('guardian.grades.index', ['student_id' => 999999]))
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// RF-04 — contrato de backend para el selector (prop `students`)
// ---------------------------------------------------------------------------

test('RF-04: con 2+ estudiantes vinculados, la prop students lista a ambos elegibles', function () {
    $ctx = guardianMultiStudentContext();

    $this->actingAs($ctx['guardian']->user)
        ->get(route('guardian.grades.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('students', 2)
            ->where('students.0.name', fn ($name) => in_array($name, [$ctx['studentA']->user->name, $ctx['studentB']->user->name], true))
        );
});

test('RF-04: con 1 solo estudiante vinculado, la prop students trae exactamente 1 entrada', function () {
    $guardian = Guardian::factory()->create();
    $student = Student::factory()->secondary()->create();
    attachStudentToGuardian($student, $guardian, isPrimary: true);
    Period::factory()->active()->create();

    $this->actingAs($guardian->user)
        ->get(route('guardian.grades.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('students', 1)
            ->where('students.0.id', $student->id)
        );
});

// ---------------------------------------------------------------------------
// RF-05 — filtro defensivo de nivel universitario (ambos controllers)
// ---------------------------------------------------------------------------

test('RF-05: un estudiante university vinculado por la fuerza no aparece en students de GradeController', function () {
    $ctx = guardianMultiStudentContext();
    $university = Student::factory()->create(); // default state = university
    attachStudentToGuardian($university, $ctx['guardian']);

    $this->actingAs($ctx['guardian']->user)
        ->get(route('guardian.grades.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('students', 2));
});

test('RF-05: student_id de un estudiante university vinculado por la fuerza responde 403', function () {
    $ctx = guardianMultiStudentContext();
    $university = Student::factory()->create();
    attachStudentToGuardian($university, $ctx['guardian']);

    $this->actingAs($ctx['guardian']->user)
        ->get(route('guardian.grades.index', ['student_id' => $university->id]))
        ->assertForbidden();
});

test('RF-05: sin student_id, el fallback nunca recae en un estudiante university vinculado por la fuerza', function () {
    $guardian = Guardian::factory()->create();
    $university = Student::factory()->create();
    attachStudentToGuardian($university, $guardian, isPrimary: true);
    $secondary = Student::factory()->secondary()->create();
    attachStudentToGuardian($secondary, $guardian);
    Period::factory()->active()->create();

    $this->actingAs($guardian->user)
        ->get(route('guardian.grades.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('student_id', $secondary->id)
            ->where('student_name', $secondary->user->name)
        );
});

test('RF-05: un estudiante university vinculado por la fuerza no aparece en el dashboard del representante', function () {
    $ctx = guardianMultiStudentContext();
    $university = Student::factory()->create();
    attachStudentToGuardian($university, $ctx['guardian']);

    $this->actingAs($ctx['guardian']->user)
        ->get(route('guardian.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('guardian/Dashboard')
            ->has('students', 2)
        );
});

test('RF-05: un representante vinculado únicamente a un estudiante university (forzado) no ve estudiantes en el dashboard', function () {
    $guardian = Guardian::factory()->create();
    $university = Student::factory()->create();
    attachStudentToGuardian($university, $guardian, isPrimary: true);

    $this->actingAs($guardian->user)
        ->get(route('guardian.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('guardian/Dashboard')
            ->has('students', 0)
        );
});
