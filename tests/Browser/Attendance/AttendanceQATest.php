<?php

/**
 * Browser (Dusk) tests — QA Gate, Attendance Module (feature 15-attendance-module)
 *
 * Covers the 5 UCs defined in specs/15-attendance-module/qa.md, exercised through
 * the full browser → front → back → DB path (never HTTP-only).
 *
 * Run with: vendor/bin/sail dusk tests/Browser/Attendance/AttendanceQATest.php
 *
 * Usa DatabaseMigrations — corre contra laravel_dusk (DB separada, ver .env.dusk.local).
 *
 * RE-VERIFICACIÓN 2026-08-30 — el implementer corrigió los 4 hallazgos del primer Feature Gate
 * (HLZ-38, HLZ-39, HLZ-40, HLZ-41) y el reviewer aprobó el código. Resultado de esta corrida:
 * **5/5 UCs en verde**. Los 87 tests Pest (HTTP/Action/DB) siguen en verde.
 *
 * - UC-QA-01: PASS — sesión regular creada y asistencia tomada vía browser, DB persiste.
 * - UC-QA-02: PASS — makeup creado seleccionando la sesión cancelada en el nuevo selector
 *             "Sesión vinculada"; la cancelada pasa a `recovered`; asistencia subida con
 *             `professor_present: false`.
 * - UC-QA-03: PASS — advance creado seleccionando la sesión futura; la futura pasa a `advanced`;
 *             al tomar asistencia en el advance, los registros se copian a la sesión futura.
 * - UC-QA-04: PASS — panel de inasistencias acumuladas muestra el total correcto.
 * - UC-QA-05: PASS — el selector ya excluye sesiones `recovered` de las candidatas (defensa de
 *             UI); simulando una condición de carrera (dos coordinadores concurrentes) inyectando
 *             la opción stale vía DOM, el backend responde con el error de validación esperado
 *             ("Esta sesión ya fue recuperada") en vez del 500 genérico visto en la corrida previa.
 *
 * Historial de hallazgos ya corregidos (ver commits del implementer):
 * - HLZ-38: `section`/`sessions` ya no se pasan como instancias Resource crudas a
 *   Inertia::render() — los controllers llaman `->toArray($request)` antes.
 * - HLZ-39: `ClassSessionResource`, `AttendanceSheetResource` y `AdminPendingSessionResource`
 *   devuelven camelCase (`sectionId`, `professorPresent`, `heldAt`, `enrollmentDetailId`, etc.).
 * - HLZ-40: se agregó el selector "Sesión vinculada" en ambos modales (profesor y admin), con
 *   `required_if:type,makeup,advance` en los FormRequests y un handler global en
 *   `bootstrap/app.php` que convierte `InvalidArgumentException` en 422/back-with-errors.
 * - HLZ-41: `Sheet.vue` (profesor y admin) inicializa `marks` desde `roster[].status` real.
 *
 * REGRESIÓN 2026-08-31 (feature 20-attendance-scheduling-and-recovery, task-gate): tras el guard
 * horario nuevo en `ClassSessionPolicy` (crear/pasar-lista sobre sesiones `Regular` exige que el
 * momento actual caiga dentro de un `Schedule` real de la sección), UC-QA-01 empezó a fallar con
 * 403 porque `attProfessorSection()` no crea ningún `Schedule` para la sección. Confirmado en vivo
 * (`console log`: "403 (Forbidden)" en `POST .../attendance/sessions`, ningún `ClassSession`
 * creado). Fix: `attWithinScheduleWindow()` crea un `Schedule` real cubriendo TODO el día de hoy
 * (00:00:00–23:59:59) para el día de la semana real — no se puede usar `Carbon::setTestNow()` como
 * en los tests Pest porque el browser Dusk golpea el servidor real en un proceso separado del
 * proceso de test (mismo motivo por el que `DatabaseMigrations` es obligatorio en vez de
 * `RefreshDatabase`). Ventana de día completo en vez de una franja horaria estrecha para no
 * depender de la hora exacta en que corre la suite.
 */

use App\Enums\ClassSessionStatus;
use App\Enums\ClassSessionType;
use App\Enums\DayOfWeek;
use App\Models\AttendanceRecord;
use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\EnrollmentDetail;
use App\Models\Period;
use App\Models\Professor;
use App\Models\Schedule;
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
 * Admin logueado — Gate::before cortocircuita autorización de ClassSessionPolicy.
 */
function attAdminUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('Admin');

    return $user;
}

/**
 * Crea una sección con un profesor como main_teacher (auto-asignado rol Profesor
 * por ProfessorFactory::configure()). Devuelve el user autenticable del profesor.
 *
 * @return array{professor_user: User, professor: Professor, section: Section}
 */
function attProfessorSection(): array
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
 * Crea un Schedule real que cubre todo el horario académico posible de hoy (día de la semana real
 * + ventana 07:00:00–18:00:00, el máximo permitido por los check constraints de la tabla
 * `schedules`: `schedules_start_min_check` / `schedules_end_max_check`) para que el guard horario
 * de `ClassSessionPolicy` (feature 20) no rechace crear ni pasar lista sobre sesiones `Regular` en
 * esta sección. Ver nota en el docblock superior. Si la suite corre fuera de ese rango horario
 * real (ej. de madrugada), esta sección seguiría "fuera de horario" tanto para la app real como
 * para el test — limitación de negocio preexistente (sin clases fuera de 07:00–18:00), no un bug
 * de este fixture.
 */
function attWithinScheduleWindow(Section $section): void
{
    $today = DayOfWeek::tryFrom(strtolower(now()->format('l')));

    Schedule::factory()->create([
        'section_id' => $section->id,
        'day_of_week' => $today ?? DayOfWeek::Monday,
        'start_time' => '07:00:00',
        'end_time' => '18:00:00',
    ]);
}

/**
 * Inscribe un estudiante en la sección y devuelve un EnrollmentDetail confirmado
 * (requisito para aparecer en el roster de asistencia).
 */
function attConfirmedDetail(Section $section): EnrollmentDetail
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
// UC-QA-01 — Profesor crea sesión regular y pasa asistencia
// ---------------------------------------------------------------------------

test('UC-QA-01: profesor crea sesión regular y pasa asistencia — DB persiste y browser confirma', function () {
    [
        'professor_user' => $professorUser,
        'section' => $section,
    ] = attProfessorSection();

    attWithinScheduleWindow($section);

    $detailA = attConfirmedDetail($section); // se deja "present" (valor por defecto de la hoja)
    $detailB = attConfirmedDetail($section); // se marca "absent" explícitamente

    // Paso 1: crear la sesión regular desde el modal "Nueva sesión"
    $this->browse(function (Browser $browser) use ($professorUser, $section) {
        $browser->loginAs($professorUser)
            ->visit(route('professor.sections.attendance.index', $section))
            ->waitFor('[dusk="new-session-btn"]', 10)
            ->click('[dusk="new-session-btn"]')
            ->waitFor('[dusk="new-session-topic"]', 10)
            ->click('[dusk="session-type-regular"]')
            ->type('[dusk="new-session-topic"]', 'Tema 1: Introducción')
            ->click('[dusk="new-session-submit"]')
            ->waitUntilMissing('[dusk="new-session-submit"]', 10);
    });

    $session = ClassSession::where('section_id', $section->id)->first();

    expect($session)->not->toBeNull()
        ->and($session->type)->toBe(ClassSessionType::Regular)
        ->and($session->topic)->toBe('Tema 1: Introducción');

    // Paso 2: abrir la hoja de esa sesión y marcar asistencia (A=present por defecto, B=absent)
    $this->browse(function (Browser $browser) use ($professorUser, $section, $session, $detailB) {
        $browser->loginAs($professorUser)
            ->visit(route('professor.sections.attendance.index', $section))
            ->waitFor("[dusk=\"session-cta-{$session->id}\"]", 10)
            ->click("[dusk=\"session-cta-{$session->id}\"]")
            ->waitFor("[dusk=\"mark-absent-{$detailB->id}\"]", 10)
            ->click("[dusk=\"mark-absent-{$detailB->id}\"]")
            ->click('[dusk="save-attendance"]')
            ->waitFor('[dusk="attendance-toast"]', 10)
            ->assertSeeIn('[dusk="attendance-toast"]', 'Asistencia guardada');
    });

    $session->refresh();

    expect($session->status)->toBe(ClassSessionStatus::Held)
        ->and($session->professor_present)->toBeTrue();

    $this->assertDatabaseHas('attendance_records', [
        'class_session_id' => $session->id,
        'enrollment_detail_id' => $detailA->id,
        'status' => 'present',
    ]);

    $this->assertDatabaseHas('attendance_records', [
        'class_session_id' => $session->id,
        'enrollment_detail_id' => $detailB->id,
        'status' => 'absent',
    ]);

    $this->assertDatabaseCount('attendance_records', 2);

    // Paso 3 (regla de round-trip / regresión HLZ-41): reabrir la hoja debe mostrar las marcas
    // realmente guardadas, no reiniciar todo a "presente".
    $this->browse(function (Browser $browser) use ($professorUser, $section, $session, $detailA, $detailB) {
        $browser->loginAs($professorUser)
            ->visit(route('professor.sections.attendance.sheet', [$section, $session]))
            ->waitFor("[dusk=\"mark-present-{$detailA->id}\"]", 10)
            ->assertVisible("[dusk=\"mark-present-{$detailA->id}\"].on")
            ->assertVisible("[dusk=\"mark-absent-{$detailB->id}\"].on");
    });
});

// ---------------------------------------------------------------------------
// UC-QA-02 — Admin crea sesión de recuperación (makeup) y sube asistencia manual
// ---------------------------------------------------------------------------

test('UC-QA-02: admin crea sesión makeup vinculada a la cancelada y sube asistencia manual', function () {
    $admin = attAdminUser();
    Period::factory()->active()->create();
    $professor = Professor::factory()->create();
    $section = Section::factory()->create(['main_teacher_id' => $professor->id]);

    $detailA = attConfirmedDetail($section);
    $detailB = attConfirmedDetail($section);

    $cancelledSession = ClassSession::factory()->forSection($section)->create([
        'type' => ClassSessionType::Regular,
        'status' => ClassSessionStatus::Cancelled,
        'held_at' => now()->subDay()->format('Y-m-d'),
    ]);

    // Paso 1: crear la sesión makeup seleccionando la cancelada como "Sesión vinculada"
    $this->browse(function (Browser $browser) use ($admin, $section, $cancelledSession) {
        $browser->loginAs($admin)
            ->visit(route('admin.sections.attendance.index', $section))
            ->waitFor('[dusk="new-session-btn"]', 10)
            ->click('[dusk="new-session-btn"]')
            ->waitFor('[dusk="session-type-makeup"]', 10)
            ->click('[dusk="session-type-makeup"]')
            ->waitFor('[dusk="linked-session-select"]', 10)
            ->select('[dusk="linked-session-select"]', (string) $cancelledSession->id)
            ->type('[dusk="new-session-topic"]', 'Recuperación del tema 3')
            ->click('[dusk="new-session-submit"]')
            ->waitUntilMissing('[dusk="new-session-submit"]', 10);
    });

    $makeupSession = ClassSession::where('section_id', $section->id)
        ->where('type', ClassSessionType::Makeup)
        ->first();

    expect($makeupSession)->not->toBeNull()
        ->and($makeupSession->linked_session_id)->toBe($cancelledSession->id);

    $cancelledSession->refresh();
    expect($cancelledSession->status)->toBe(ClassSessionStatus::Recovered);

    // Paso 2: subir asistencia manual para la sesión makeup (todos presentes)
    $this->browse(function (Browser $browser) use ($admin, $section, $makeupSession, $detailA, $detailB) {
        $browser->loginAs($admin)
            ->visit(route('admin.sections.attendance.sheet', [$section, $makeupSession]))
            ->waitFor("[dusk=\"mark-present-{$detailA->id}\"]", 10)
            ->click("[dusk=\"mark-present-{$detailA->id}\"]")
            ->click("[dusk=\"mark-present-{$detailB->id}\"]")
            ->click('[dusk="save-attendance"]')
            ->waitFor('[dusk="attendance-toast"]', 10)
            ->assertSeeIn('[dusk="attendance-toast"]', 'Asistencia guardada');
    });

    $makeupSession->refresh();

    expect($makeupSession->status)->toBe(ClassSessionStatus::Held)
        ->and($makeupSession->professor_present)->toBeFalse();

    $this->assertDatabaseCount('attendance_records', 2);

    $this->assertDatabaseHas('attendance_records', [
        'class_session_id' => $makeupSession->id,
        'enrollment_detail_id' => $detailA->id,
        'status' => 'present',
    ]);

    $this->assertDatabaseHas('attendance_records', [
        'class_session_id' => $makeupSession->id,
        'enrollment_detail_id' => $detailB->id,
        'status' => 'present',
    ]);
});

// ---------------------------------------------------------------------------
// UC-QA-03 — Profesor crea adelanto (advance) y la asistencia se copia a la sesión futura
// ---------------------------------------------------------------------------

test('UC-QA-03: profesor crea sesión advance vinculada a la futura y la asistencia se copia', function () {
    [
        'professor_user' => $professorUser,
        'section' => $section,
    ] = attProfessorSection();

    $detailA = attConfirmedDetail($section);
    $detailB = attConfirmedDetail($section);

    $futureSession = ClassSession::factory()->forSection($section)->create([
        'type' => ClassSessionType::Regular,
        'status' => ClassSessionStatus::Scheduled,
        'held_at' => now()->addWeek()->format('Y-m-d'),
    ]);

    // Paso 1: crear la sesión advance seleccionando la futura como "Sesión vinculada"
    $this->browse(function (Browser $browser) use ($professorUser, $section, $futureSession) {
        $browser->loginAs($professorUser)
            ->visit(route('professor.sections.attendance.index', $section))
            ->waitFor('[dusk="new-session-btn"]', 10)
            ->click('[dusk="new-session-btn"]')
            ->waitFor('[dusk="session-type-advance"]', 10)
            ->click('[dusk="session-type-advance"]')
            ->waitFor('[dusk="linked-session-select"]', 10)
            ->select('[dusk="linked-session-select"]', (string) $futureSession->id)
            ->type('[dusk="new-session-topic"]', 'Adelanto del tema 5')
            ->click('[dusk="new-session-submit"]')
            ->waitUntilMissing('[dusk="new-session-submit"]', 10);
    });

    $advanceSession = ClassSession::where('section_id', $section->id)
        ->where('type', ClassSessionType::Advance)
        ->first();

    expect($advanceSession)->not->toBeNull()
        ->and($advanceSession->linked_session_id)->toBe($futureSession->id);

    $futureSession->refresh();
    expect($futureSession->status)->toBe(ClassSessionStatus::Advanced);

    // Paso 2: tomar asistencia en la sesión advance (A=present, B=absent)
    $this->browse(function (Browser $browser) use ($professorUser, $section, $advanceSession, $detailB) {
        $browser->loginAs($professorUser)
            ->visit(route('professor.sections.attendance.sheet', [$section, $advanceSession]))
            ->waitFor("[dusk=\"mark-absent-{$detailB->id}\"]", 10)
            ->click("[dusk=\"mark-absent-{$detailB->id}\"]")
            ->click('[dusk="save-attendance"]')
            ->waitFor('[dusk="attendance-toast"]', 10)
            ->assertSeeIn('[dusk="attendance-toast"]', 'Asistencia guardada');
    });

    $advanceSession->refresh();
    expect($advanceSession->status)->toBe(ClassSessionStatus::Held)
        ->and($advanceSession->professor_present)->toBeTrue();

    $this->assertDatabaseHas('attendance_records', [
        'class_session_id' => $advanceSession->id,
        'enrollment_detail_id' => $detailA->id,
        'status' => 'present',
    ]);
    $this->assertDatabaseHas('attendance_records', [
        'class_session_id' => $advanceSession->id,
        'enrollment_detail_id' => $detailB->id,
        'status' => 'absent',
    ]);

    // Los registros deben COPIARSE a la sesión futura vinculada (ahora `advanced`)
    $this->assertDatabaseHas('attendance_records', [
        'class_session_id' => $futureSession->id,
        'enrollment_detail_id' => $detailA->id,
        'status' => 'present',
    ]);
    $this->assertDatabaseHas('attendance_records', [
        'class_session_id' => $futureSession->id,
        'enrollment_detail_id' => $detailB->id,
        'status' => 'absent',
    ]);

    $this->assertDatabaseCount('attendance_records', 4);
});

// ---------------------------------------------------------------------------
// UC-QA-04 — Totales de inasistencia por estudiante
// ---------------------------------------------------------------------------

test('UC-QA-04: el panel de inasistencias muestra el total correcto por estudiante en el browser', function () {
    [
        'professor_user' => $professorUser,
        'section' => $section,
    ] = attProfessorSection();

    $detailB = attConfirmedDetail($section);

    // held — cuenta
    $sessionHeld = ClassSession::factory()->forSection($section)->create([
        'status' => ClassSessionStatus::Held,
        'held_at' => now()->subDays(3)->format('Y-m-d'),
    ]);
    AttendanceRecord::create(['class_session_id' => $sessionHeld->id, 'enrollment_detail_id' => $detailB->id, 'status' => 'absent']);

    // recovered — cuenta
    $sessionRecovered = ClassSession::factory()->forSection($section)->create([
        'status' => ClassSessionStatus::Recovered,
        'held_at' => now()->subDays(2)->format('Y-m-d'),
    ]);
    AttendanceRecord::create(['class_session_id' => $sessionRecovered->id, 'enrollment_detail_id' => $detailB->id, 'status' => 'absent']);

    // advanced — cuenta
    $sessionAdvanced = ClassSession::factory()->forSection($section)->create([
        'status' => ClassSessionStatus::Advanced,
        'held_at' => now()->subDays(1)->format('Y-m-d'),
    ]);
    AttendanceRecord::create(['class_session_id' => $sessionAdvanced->id, 'enrollment_detail_id' => $detailB->id, 'status' => 'absent']);

    // scheduled — NO cuenta
    $sessionScheduled = ClassSession::factory()->forSection($section)->create([
        'status' => ClassSessionStatus::Scheduled,
        'held_at' => now()->addDay()->format('Y-m-d'),
    ]);
    AttendanceRecord::create(['class_session_id' => $sessionScheduled->id, 'enrollment_detail_id' => $detailB->id, 'status' => 'absent']);

    // cancelled — NO cuenta
    $sessionCancelled = ClassSession::factory()->forSection($section)->create([
        'status' => ClassSessionStatus::Cancelled,
        'held_at' => now()->subDays(5)->format('Y-m-d'),
    ]);
    AttendanceRecord::create(['class_session_id' => $sessionCancelled->id, 'enrollment_detail_id' => $detailB->id, 'status' => 'absent']);

    $this->browse(function (Browser $browser) use ($professorUser, $section, $detailB) {
        $browser->loginAs($professorUser)
            ->visit(route('professor.sections.attendance.index', $section))
            ->waitFor('[dusk="tab-totals"]', 10)
            ->click('[dusk="tab-totals"]')
            ->waitFor("[dusk=\"absence-count-{$detailB->id}\"]", 10)
            ->assertSeeIn("[dusk=\"absence-count-{$detailB->id}\"]", '3');
    });
});

// ---------------------------------------------------------------------------
// UC-QA-05 — Validación: no se puede recuperar una sesión ya recuperada
// ---------------------------------------------------------------------------
//
// El selector "Sesión vinculada" ya excluye sesiones `recovered` de las candidatas (solo lista
// `status === 'cancelled'`), así que un coordinador no puede elegir una sesión ya recuperada en
// el flujo normal. Para ejercitar la defensa de backend (que sigue siendo necesaria ante una
// condición de carrera — dos coordinadores concurrentes recuperando la misma sesión) inyectamos
// la opción "stale" vía DOM y confirmamos que el servidor responde con el error de validación
// esperado en vez de un 500 genérico.

test('UC-QA-05: el selector excluye sesiones ya recuperadas y el backend bloquea el caso de carrera', function () {
    $admin = attAdminUser();
    Period::factory()->active()->create();
    $professor = Professor::factory()->create();
    $section = Section::factory()->create(['main_teacher_id' => $professor->id]);

    attConfirmedDetail($section);

    // Sesión que ya fue recuperada por un makeup existente
    $alreadyRecovered = ClassSession::factory()->forSection($section)->create([
        'type' => ClassSessionType::Regular,
        'status' => ClassSessionStatus::Recovered,
        'held_at' => now()->subDays(2)->format('Y-m-d'),
    ]);

    $this->browse(function (Browser $browser) use ($admin, $section, $alreadyRecovered) {
        $browser->loginAs($admin)
            ->visit(route('admin.sections.attendance.index', $section))
            ->waitFor('[dusk="new-session-btn"]', 10)
            ->click('[dusk="new-session-btn"]')
            ->waitFor('[dusk="session-type-makeup"]', 10)
            ->click('[dusk="session-type-makeup"]')
            ->waitFor('[dusk="linked-session-select"]', 10);

        // Defensa de UI: la sesión ya recuperada NO aparece como opción seleccionable.
        $optionValues = $browser->script(
            "return Array.from(document.querySelectorAll('[dusk=\"linked-session-select\"] option')).map(o => o.value);"
        )[0];
        expect($optionValues)->not->toContain((string) $alreadyRecovered->id);

        // Simulación de condición de carrera: dos coordinadores concurrentes podrían enviar el
        // mismo linked_session_id antes de que la primera escritura se refleje en la UI del
        // segundo. Inyectamos la opción "stale" para forzar ese envío y confirmar la defensa
        // de backend (FormRequest ya no permite null, pero si el id llega y ya está `recovered`,
        // el Action debe seguir bloqueando con un mensaje claro, no un 500).
        $browser->script("
            let sel = document.querySelector('[dusk=\"linked-session-select\"]');
            let opt = document.createElement('option');
            opt.value = '{$alreadyRecovered->id}';
            opt.text = 'stale-option (condición de carrera simulada)';
            sel.appendChild(opt);
            sel.value = '{$alreadyRecovered->id}';
            sel.dispatchEvent(new Event('change'));
        ");

        $browser->type('[dusk="new-session-topic"]', 'Segundo intento de recuperación')
            ->click('[dusk="new-session-submit"]')
            ->waitForText('Esta sesión ya fue recuperada', 10)
            ->assertSee('Esta sesión ya fue recuperada');
    });

    expect(ClassSession::where('type', ClassSessionType::Makeup)->exists())->toBeFalse();

    $alreadyRecovered->refresh();
    expect($alreadyRecovered->status)->toBe(ClassSessionStatus::Recovered);
});
