<?php

/**
 * Browser (Dusk) tests — QA Gate, feature 20-attendance-scheduling-and-recovery (HLZ-44 + HLZ-46)
 *
 * Covers the UCs marked "Test Dusk: pendiente" in specs/20-attendance-scheduling-and-recovery/qa.md
 * (UC-QA-01, UC-QA-03, UC-QA-04, UC-QA-07), exercised through the full browser → front → back → DB
 * path. UC-QA-02, UC-QA-05 and UC-QA-06 are backend-only per qa.md's own decision (already covered
 * as Feature/Pest tests in tests/Feature/AttendanceSchedulingAndRecovery/Acceptance/), and are not
 * duplicated here.
 *
 * Run with: vendor/bin/sail dusk tests/Browser/Attendance/AttendanceSchedulingAndRecoveryTest.php
 *
 * Usa DatabaseMigrations — corre contra laravel_dusk (DB separada, ver .env.dusk.local).
 *
 * Convenciones reutilizadas de tests/Browser/Attendance/AttendanceQATest.php (feature 15), con
 * nombres de helper distintos (`schedXxx`) para evitar colisión de funciones globales de Pest
 * dentro del mismo proceso de la suite Dusk.
 *
 * Nota sobre el guard horario y Dusk: el navegador y el servidor de la app corren en procesos
 * separados (igual que con DatabaseMigrations vs RefreshDatabase), así que `Carbon::setTestNow()`
 * NO tiene efecto sobre el servidor real. Para garantizar de forma determinista que "el momento
 * actual está fuera de la ventana horaria de la sección" sin importar cuándo corra la suite, las
 * secciones de estos tests NO tienen ningún `Schedule` asociado — sin ningún Schedule, ningún
 * momento puede caer "dentro" de la ventana (verdad vacua), cumpliendo la precondición de
 * UC-QA-01/UC-QA-03 sin depender del reloj real.
 *
 * Estado esperado al escribir este archivo (sin el fix de design.md aplicado):
 * - UC-QA-01: ROJO — hoy no existe ningún guard horario, así que crear una sesión Regular sin
 *   ningún Schedule real responde 200/302 (sesión creada), no 403. El test espera ver la página
 *   "Sin permisos para esta sección" (errors/AccessDenied.vue) y no la encuentra.
 * - UC-QA-03: VERDE ya — es un test de regresión (Adelanto/Recuperación nunca tuvieron guard
 *   horario y no deben tenerlo tras el fix). Se documenta igual que en
 *   tests/Feature/AttendanceAdvanceGuardFix/Acceptance/AdvanceGuardTest.php.
 * - UC-QA-04 y UC-QA-07: ROJO — no existe el botón "Cancelar" en la UI (`AttSessionCard.vue` no
 *   tiene ningún `dusk="cancel-session-{id}"`), ni la ruta PATCH .../cancel, ni el
 *   `CancelClassSessionAction`. `waitFor('[dusk="cancel-session-{id}"]')` agota el timeout.
 *
 * Decisión de UI asumida para el botón "Cancelar" (no confirmable hasta que el implementer
 * construya la UI real, ver design.md punto 6): confirmación vía diálogo nativo del navegador
 * (`window.confirm()`), porque no existe ningún componente de modal de confirmación reutilizable
 * en `components/UI/` en este momento. Si el implementer opta por un modal custom en su lugar, el
 * tester en modo `task-gate`/`feature-gate` debe ajustar `waitForDialog()->acceptDialog()` por el
 * flujo real, igual que ya se permite agregar atributos `dusk` faltantes en esos modos.
 */

use App\Enums\ClassSessionStatus;
use App\Enums\ClassSessionType;
use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\EnrollmentDetail;
use App\Models\Period;
use App\Models\Professor;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Spatie\Permission\PermissionRegistrar;

uses(DatabaseMigrations::class);

// ---------------------------------------------------------------------------
// Setup
// ---------------------------------------------------------------------------

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Crea una sección con un profesor como main_teacher. Deliberadamente NO crea ningún Schedule
 * — ver nota en el docblock superior sobre por qué esto garantiza "fuera de horario" sin
 * depender del reloj real del proceso del servidor.
 *
 * @return array{professor_user: User, professor: Professor, section: Section}
 */
function schedProfessorSection(): array
{
    Period::factory()->active()->create();
    $professor = Professor::factory()->create();

    $section = Section::factory()->create([
        'main_teacher_id' => $professor->id,
    ]);

    return [
        'professor_user' => $professor->user,
        'professor' => $professor,
        'section' => $section,
    ];
}

/**
 * Inscribe un estudiante en la sección y devuelve un EnrollmentDetail confirmado.
 */
function schedConfirmedDetail(Section $section): EnrollmentDetail
{
    $student = Student::factory()->create();
    $enrollment = Enrollment::factory()->create([
        'student_id' => $student->id,
        'period_id' => $section->period_id,
    ]);

    return EnrollmentDetail::factory()->confirmed()->create([
        'enrollment_id' => $enrollment->id,
        'section_id' => $section->id,
    ]);
}

// ---------------------------------------------------------------------------
// UC-QA-01 — Crear sesión Regular fuera del horario real responde 403 (visible en browser)
// ---------------------------------------------------------------------------

test('UC-QA-01: crear sesión Regular fuera del horario real de la sección responde 403 en el browser', function () {
    [
        'professor_user' => $professorUser,
        'section' => $section,
    ] = schedProfessorSection();

    $this->browse(function (Browser $browser) use ($professorUser, $section) {
        $browser->loginAs($professorUser)
            ->visit(route('professor.sections.attendance.index', $section))
            ->waitFor('[dusk="new-session-btn"]', 10)
            ->click('[dusk="new-session-btn"]')
            ->waitFor('[dusk="new-session-topic"]', 10)
            ->click('[dusk="session-type-regular"]')
            ->type('[dusk="new-session-topic"]', 'Clase fuera de horario')
            ->click('[dusk="new-session-submit"]')
            ->waitForText('Sin permisos para esta sección', 10)
            ->assertSee('Sin permisos para esta sección');
    });

    expect(ClassSession::where('section_id', $section->id)->exists())->toBeFalse();
});

// ---------------------------------------------------------------------------
// UC-QA-03 — Adelanto y Recuperación siguen funcionando fuera de horario (regresión)
// ---------------------------------------------------------------------------

test('UC-QA-03: Adelanto y Recuperación siguen funcionando sin restricción horaria (regresión, exentos por diseño)', function () {
    [
        'professor_user' => $professorUser,
        'section' => $section,
    ] = schedProfessorSection();

    $detailA = schedConfirmedDetail($section);
    $detailB = schedConfirmedDetail($section);

    $cancelledSession = ClassSession::factory()->forSection($section)->create([
        'type' => ClassSessionType::Regular,
        'status' => ClassSessionStatus::Cancelled,
        'held_at' => now()->subDay()->format('Y-m-d'),
    ]);

    $futureSession = ClassSession::factory()->forSection($section)->create([
        'type' => ClassSessionType::Regular,
        'status' => ClassSessionStatus::Scheduled,
        'held_at' => now()->addWeek()->format('Y-m-d'),
    ]);

    // Paso 1: crear el Adelanto (advance) vinculado a la sesión futura — sin Schedule alguno.
    $this->browse(function (Browser $browser) use ($professorUser, $section, $futureSession) {
        $browser->loginAs($professorUser)
            ->visit(route('professor.sections.attendance.index', $section))
            ->waitFor('[dusk="new-session-btn"]', 10)
            ->click('[dusk="new-session-btn"]')
            ->waitFor('[dusk="session-type-advance"]', 10)
            ->click('[dusk="session-type-advance"]')
            ->waitFor('[dusk="linked-session-select"]', 10)
            ->select('[dusk="linked-session-select"]', (string) $futureSession->id)
            ->type('[dusk="new-session-topic"]', 'Adelanto sin restricción horaria')
            ->click('[dusk="new-session-submit"]')
            ->waitUntilMissing('[dusk="new-session-submit"]', 10);
    });

    $advanceSession = ClassSession::where('section_id', $section->id)
        ->where('type', ClassSessionType::Advance)
        ->first();

    expect($advanceSession)->not->toBeNull();
    $futureSession->refresh();
    expect($futureSession->status)->toBe(ClassSessionStatus::Advanced);

    // Paso 2: pasar lista en el Adelanto — debe funcionar sin bloqueo.
    $this->browse(function (Browser $browser) use ($professorUser, $section, $advanceSession, $detailA, $detailB) {
        $browser->loginAs($professorUser)
            ->visit(route('professor.sections.attendance.sheet', [$section, $advanceSession]))
            ->waitFor("[dusk=\"mark-present-{$detailA->id}\"]", 10)
            ->click("[dusk=\"mark-present-{$detailA->id}\"]")
            ->click("[dusk=\"mark-absent-{$detailB->id}\"]")
            ->click('[dusk="save-attendance"]')
            ->waitFor('[dusk="attendance-toast"]', 10)
            ->assertSeeIn('[dusk="attendance-toast"]', 'Asistencia guardada');
    });

    $advanceSession->refresh();
    expect($advanceSession->status)->toBe(ClassSessionStatus::Held);

    // Paso 3: crear la Recuperación (makeup) vinculada a la sesión cancelada.
    $this->browse(function (Browser $browser) use ($professorUser, $section, $cancelledSession) {
        $browser->loginAs($professorUser)
            ->visit(route('professor.sections.attendance.index', $section))
            ->waitFor('[dusk="new-session-btn"]', 10)
            ->click('[dusk="new-session-btn"]')
            ->waitFor('[dusk="session-type-makeup"]', 10)
            ->click('[dusk="session-type-makeup"]')
            ->waitFor('[dusk="linked-session-select"]', 10)
            ->select('[dusk="linked-session-select"]', (string) $cancelledSession->id)
            ->type('[dusk="new-session-topic"]', 'Recuperación sin restricción horaria')
            ->click('[dusk="new-session-submit"]')
            ->waitUntilMissing('[dusk="new-session-submit"]', 10);
    });

    $makeupSession = ClassSession::where('section_id', $section->id)
        ->where('type', ClassSessionType::Makeup)
        ->first();

    expect($makeupSession)->not->toBeNull();
    $cancelledSession->refresh();
    expect($cancelledSession->status)->toBe(ClassSessionStatus::Recovered);

    // Paso 4: pasar lista en la Recuperación — debe funcionar sin bloqueo.
    $this->browse(function (Browser $browser) use ($professorUser, $section, $makeupSession, $detailA) {
        $browser->loginAs($professorUser)
            ->visit(route('professor.sections.attendance.sheet', [$section, $makeupSession]))
            ->waitFor("[dusk=\"mark-present-{$detailA->id}\"]", 10)
            ->click("[dusk=\"mark-present-{$detailA->id}\"]")
            ->click('[dusk="save-attendance"]')
            ->waitFor('[dusk="attendance-toast"]', 10)
            ->assertSeeIn('[dusk="attendance-toast"]', 'Asistencia guardada');
    });

    $makeupSession->refresh();
    expect($makeupSession->status)->toBe(ClassSessionStatus::Held);
});

// ---------------------------------------------------------------------------
// UC-QA-04 — Cancelar una sesión scheduled la deja disponible para Recuperación
// ---------------------------------------------------------------------------

test('UC-QA-04: cancelar una sesión scheduled la deja disponible en el selector de Recuperación', function () {
    [
        'professor_user' => $professorUser,
        'section' => $section,
    ] = schedProfessorSection();

    $session = ClassSession::factory()->forSection($section)->create([
        'type' => ClassSessionType::Regular,
        'status' => ClassSessionStatus::Scheduled,
        'held_at' => now()->addDays(2)->format('Y-m-d'),
    ]);

    // Paso 1: cancelar la sesión desde la UI.
    $this->browse(function (Browser $browser) use ($professorUser, $section, $session) {
        $browser->loginAs($professorUser)
            ->visit(route('professor.sections.attendance.index', $section))
            ->waitFor("[dusk=\"cancel-session-{$session->id}\"]", 10)
            ->click("[dusk=\"cancel-session-{$session->id}\"]")
            ->waitForDialog(5)
            ->acceptDialog()
            ->waitUntilMissing("[dusk=\"cancel-session-{$session->id}\"]", 10);
    });

    $session->refresh();
    expect($session->status)->toBe(ClassSessionStatus::Cancelled);

    // Paso 2: abrir "Nueva sesión" → Recuperación y verificar que aparece como candidata.
    $this->browse(function (Browser $browser) use ($professorUser, $section, $session) {
        $browser->loginAs($professorUser)
            ->visit(route('professor.sections.attendance.index', $section))
            ->waitFor('[dusk="new-session-btn"]', 10)
            ->click('[dusk="new-session-btn"]')
            ->waitFor('[dusk="session-type-makeup"]', 10)
            ->click('[dusk="session-type-makeup"]')
            ->waitFor('[dusk="linked-session-select"]', 10);

        $optionValues = $browser->script(
            "return Array.from(document.querySelectorAll('[dusk=\"linked-session-select\"] option')).map(o => o.value);"
        )[0];

        expect($optionValues)->toContain((string) $session->id);
    });
});

// ---------------------------------------------------------------------------
// UC-QA-07 — Recuperación de punta a punta contra una sesión recién cancelada
// ---------------------------------------------------------------------------

test('UC-QA-07: recuperación de punta a punta contra una sesión recién cancelada', function () {
    [
        'professor_user' => $professorUser,
        'section' => $section,
    ] = schedProfessorSection();

    $detailA = schedConfirmedDetail($section);
    $detailB = schedConfirmedDetail($section);

    $target = ClassSession::factory()->forSection($section)->create([
        'type' => ClassSessionType::Regular,
        'status' => ClassSessionStatus::Scheduled,
        'held_at' => now()->addDays(2)->format('Y-m-d'),
    ]);

    // Paso 1: cancelar la sesión objetivo desde la UI.
    $this->browse(function (Browser $browser) use ($professorUser, $section, $target) {
        $browser->loginAs($professorUser)
            ->visit(route('professor.sections.attendance.index', $section))
            ->waitFor("[dusk=\"cancel-session-{$target->id}\"]", 10)
            ->click("[dusk=\"cancel-session-{$target->id}\"]")
            ->waitForDialog(5)
            ->acceptDialog()
            ->waitUntilMissing("[dusk=\"cancel-session-{$target->id}\"]", 10);
    });

    $target->refresh();
    expect($target->status)->toBe(ClassSessionStatus::Cancelled);

    // Paso 2: crear la Recuperación (makeup) vinculada a la sesión recién cancelada.
    $this->browse(function (Browser $browser) use ($professorUser, $section, $target) {
        $browser->loginAs($professorUser)
            ->visit(route('professor.sections.attendance.index', $section))
            ->waitFor('[dusk="new-session-btn"]', 10)
            ->click('[dusk="new-session-btn"]')
            ->waitFor('[dusk="session-type-makeup"]', 10)
            ->click('[dusk="session-type-makeup"]')
            ->waitFor('[dusk="linked-session-select"]', 10)
            ->select('[dusk="linked-session-select"]', (string) $target->id)
            ->type('[dusk="new-session-topic"]', 'Recuperación punta a punta')
            ->click('[dusk="new-session-submit"]')
            ->waitUntilMissing('[dusk="new-session-submit"]', 10);
    });

    $makeupSession = ClassSession::where('section_id', $section->id)
        ->where('type', ClassSessionType::Makeup)
        ->first();

    expect($makeupSession)->not->toBeNull()
        ->and($makeupSession->linked_session_id)->toBe($target->id);

    $target->refresh();
    expect($target->status)->toBe(ClassSessionStatus::Recovered);

    // Paso 3: pasar lista en la Recuperación (A presente, B ausente).
    $this->browse(function (Browser $browser) use ($professorUser, $section, $makeupSession, $detailB) {
        $browser->loginAs($professorUser)
            ->visit(route('professor.sections.attendance.sheet', [$section, $makeupSession]))
            ->waitFor("[dusk=\"mark-absent-{$detailB->id}\"]", 10)
            ->click("[dusk=\"mark-absent-{$detailB->id}\"]")
            ->click('[dusk="save-attendance"]')
            ->waitFor('[dusk="attendance-toast"]', 10)
            ->assertSeeIn('[dusk="attendance-toast"]', 'Asistencia guardada');
    });

    $makeupSession->refresh();
    expect($makeupSession->status)->toBe(ClassSessionStatus::Held);

    $this->assertDatabaseHas('attendance_records', [
        'class_session_id' => $makeupSession->id,
        'enrollment_detail_id' => $detailA->id,
        'status' => 'present',
    ]);
    $this->assertDatabaseHas('attendance_records', [
        'class_session_id' => $makeupSession->id,
        'enrollment_detail_id' => $detailB->id,
        'status' => 'absent',
    ]);
    $this->assertDatabaseCount('attendance_records', 2);

    // La sesión objetivo cancelada queda en su estado terminal `recovered`.
    $target->refresh();
    expect($target->status)->toBe(ClassSessionStatus::Recovered);
});
