<?php

/**
 * Acceptance tests — HLZ-44 (feature 20-attendance-scheduling-and-recovery)
 *
 * Contrato: `ClassSessionPolicy::create()` y `ClassSessionPolicy::takeAttendance()` deben
 * rechazar (403) operaciones sobre sesiones `type=regular` cuando el momento actual no cae
 * dentro de ningún `Schedule` real de la sección (día de la semana + hora). Sesiones `makeup`
 * y `advance` están exentas por diseño (regresión, sin cambios de comportamiento).
 *
 * Cubre specs/20-attendance-scheduling-and-recovery/requirements.md:
 * - RF-01: POST .../attendance/sessions con type=regular fuera de ventana horaria → 403.
 * - RF-02: PUT .../attendance/sessions/{classSession} (pasar lista) sobre type=regular fuera
 *          de ventana horaria → 403.
 * - RF-03: crear/pasar lista en type=makeup o type=advance no tiene restricción horaria.
 * - RF-04: lo mismo aplica en el flujo Admin (`/admin/sections/{section}/attendance*`).
 *
 * Intocable por el implementer — define el contrato antes del fix. En el momento de escribir
 * este archivo, `ClassSessionPolicy` NO tiene ningún guard horario (ver
 * app/Policies/ClassSessionPolicy.php), así que los tests RF-01/RF-02/RF-04 (caso "fuera de
 * horario") deben estar en ROJO. Los tests marcados "(regresión)" deben estar en VERDE desde
 * ya — son el comportamiento actual, correcto, que no debe romperse (mismo patrón usado en
 * tests/Feature/AttendanceAdvanceGuardFix/Acceptance/AdvanceGuardTest.php).
 *
 * Decisión de diseño sobre RF-04 y Gate::before: `AppServiceProvider` registra
 * `Gate::before(fn ($user) => $user->hasRole('Admin') ? true : null)`, que concede acceso total
 * a cualquier usuario con rol 'Admin' para CUALQUIER ability, incluida esta — exactamente el
 * mismo patrón que design.md documenta explícitamente para RF-08 (cancelar). Por lo tanto, para
 * un usuario con rol 'Admin' real, el guard horario nunca se evalúa (bypass legítimo y ya
 * existente en toda la aplicación, no un bug de este feature). Los tests de RF-04 verifican el
 * camino que sí ejercita `ClassSessionPolicy::isWithinScheduleWindow()` a través del controller
 * Admin: el profesor dueño de la sección accediendo directamente a las rutas
 * `/admin/sections/{section}/attendance*` (permitido: ese grupo de rutas solo exige
 * `['auth','verified']`, sin restricción de rol) — así se confirma que el fix en
 * `AdminStoreClassSessionRequest::authorize()` (pasar `$this->input('type')` al Policy) está
 * correctamente cableado, sin depender de si quien la usa además tiene rol Admin.
 */

use App\Enums\ClassSessionStatus;
use App\Enums\ClassSessionType;
use App\Enums\DayOfWeek;
use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\EnrollmentDetail;
use App\Models\Period;
use App\Models\Professor;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
});

afterEach(function () {
    Carbon::setTestNow();
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Profesor dueño de una sección con período activo — contexto base para los tests.
 *
 * @return array{professor: Professor, section: Section}
 */
function schedGuardProfessorContext(): array
{
    $professor = Professor::factory()->create();
    $period = Period::factory()->active()->create();
    $section = Section::factory()->create([
        'period_id' => $period->id,
        'main_teacher_id' => $professor->id,
    ]);

    return compact('professor', 'section');
}

/**
 * Crea un EnrollmentDetail confirmado para la sección (roster elegible para pasar lista).
 */
function schedGuardConfirmedDetail(Section $section): EnrollmentDetail
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

/**
 * Fija Carbon::now() a un día de la semana + hora exactos, usando fechas ancla conocidas
 * (enero de 2024, donde el 1 de enero cae exactamente en lunes) para no depender del día real
 * en que corre la suite.
 */
function schedGuardTravelTo(DayOfWeek $day, string $time): void
{
    $anchorDate = match ($day) {
        DayOfWeek::Monday => '2024-01-01',
        DayOfWeek::Tuesday => '2024-01-02',
        DayOfWeek::Wednesday => '2024-01-03',
        DayOfWeek::Thursday => '2024-01-04',
        DayOfWeek::Friday => '2024-01-05',
        DayOfWeek::Saturday => '2024-01-06',
    };

    Carbon::setTestNow(Carbon::parse("{$anchorDate} {$time}"));
}

// ---------------------------------------------------------------------------
// RF-01 — Crear sesión Regular fuera de la ventana horaria real → 403
// ---------------------------------------------------------------------------

test('RF-01: profesor no puede crear una sesión Regular fuera del horario real de la sección (403)', function () {
    ['professor' => $professor, 'section' => $section] = schedGuardProfessorContext();

    Schedule::factory()->create([
        'section_id' => $section->id,
        'day_of_week' => DayOfWeek::Monday,
        'start_time' => '08:00:00',
        'end_time' => '08:45:00',
    ]);

    // Momento actual: martes 10:00 — ningún Schedule de la sección cubre este momento.
    schedGuardTravelTo(DayOfWeek::Tuesday, '10:00:00');

    $response = $this->actingAs($professor->user)
        ->post(route('professor.sections.attendance.sessions.store', $section), [
            'type' => 'regular',
            'topic' => 'Clase fuera de horario',
            'held_at' => null,
        ]);

    $response->assertForbidden();

    $this->assertDatabaseMissing('class_sessions', ['topic' => 'Clase fuera de horario']);
    $this->assertDatabaseCount('class_sessions', 0);
});

test('RF-01 (regresión): profesor puede crear una sesión Regular dentro del horario real de la sección', function () {
    ['professor' => $professor, 'section' => $section] = schedGuardProfessorContext();

    Schedule::factory()->create([
        'section_id' => $section->id,
        'day_of_week' => DayOfWeek::Monday,
        'start_time' => '08:00:00',
        'end_time' => '08:45:00',
    ]);

    // Momento actual: lunes 08:20 — cae dentro del Schedule real de la sección.
    schedGuardTravelTo(DayOfWeek::Monday, '08:20:00');

    $response = $this->actingAs($professor->user)
        ->post(route('professor.sections.attendance.sessions.store', $section), [
            'type' => 'regular',
            'topic' => 'Clase dentro de horario',
            'held_at' => null,
        ]);

    $response->assertSessionDoesntHaveErrors()
        ->assertRedirect(route('professor.sections.attendance.index', $section));

    $this->assertDatabaseHas('class_sessions', [
        'section_id' => $section->id,
        'type' => ClassSessionType::Regular->value,
        'topic' => 'Clase dentro de horario',
    ]);
    $this->assertDatabaseCount('class_sessions', 1);
});

test('RF-01 (bordes): los límites start_time/end_time del Schedule son inclusivos', function (string $time) {
    ['professor' => $professor, 'section' => $section] = schedGuardProfessorContext();

    Schedule::factory()->create([
        'section_id' => $section->id,
        'day_of_week' => DayOfWeek::Monday,
        'start_time' => '08:00:00',
        'end_time' => '08:45:00',
    ]);

    schedGuardTravelTo(DayOfWeek::Monday, $time);

    $response = $this->actingAs($professor->user)
        ->post(route('professor.sections.attendance.sessions.store', $section), [
            'type' => 'regular',
            'topic' => "Clase en el borde {$time}",
            'held_at' => null,
        ]);

    $response->assertSessionDoesntHaveErrors();
    $this->assertDatabaseHas('class_sessions', ['topic' => "Clase en el borde {$time}"]);
})->with([
    'inicio exacto' => ['08:00:00'],
    'fin exacto' => ['08:45:00'],
]);

// ---------------------------------------------------------------------------
// RF-02 — Pasar lista en sesión Regular fuera de la ventana horaria real → 403
// ---------------------------------------------------------------------------

test('RF-02: profesor no puede pasar lista en una sesión Regular fuera del horario real (403)', function () {
    ['professor' => $professor, 'section' => $section] = schedGuardProfessorContext();
    $detail = schedGuardConfirmedDetail($section);

    Schedule::factory()->create([
        'section_id' => $section->id,
        'day_of_week' => DayOfWeek::Monday,
        'start_time' => '08:00:00',
        'end_time' => '08:45:00',
    ]);

    $session = ClassSession::factory()->forSection($section)->create([
        'type' => ClassSessionType::Regular,
        'status' => ClassSessionStatus::Scheduled,
        'held_at' => null,
    ]);

    // Momento actual: sábado 12:00 — ningún Schedule de la sección cubre este momento.
    schedGuardTravelTo(DayOfWeek::Saturday, '12:00:00');

    $response = $this->actingAs($professor->user)
        ->put(route('professor.sections.attendance.upsert', [$section, $session]), [
            'marks' => [$detail->id => 'present'],
            'professor_present' => true,
        ]);

    $response->assertForbidden();

    $session->refresh();
    expect($session->status)->toBe(ClassSessionStatus::Scheduled);
    $this->assertDatabaseCount('attendance_records', 0);
});

test('RF-02 (regresión): profesor puede pasar lista en una sesión Regular dentro del horario real', function () {
    ['professor' => $professor, 'section' => $section] = schedGuardProfessorContext();
    $detail = schedGuardConfirmedDetail($section);

    Schedule::factory()->create([
        'section_id' => $section->id,
        'day_of_week' => DayOfWeek::Monday,
        'start_time' => '08:00:00',
        'end_time' => '08:45:00',
    ]);

    $session = ClassSession::factory()->forSection($section)->create([
        'type' => ClassSessionType::Regular,
        'status' => ClassSessionStatus::Scheduled,
        'held_at' => null,
    ]);

    schedGuardTravelTo(DayOfWeek::Monday, '08:30:00');

    $response = $this->actingAs($professor->user)
        ->put(route('professor.sections.attendance.upsert', [$section, $session]), [
            'marks' => [$detail->id => 'present'],
            'professor_present' => true,
        ]);

    $response->assertRedirect(route('professor.sections.attendance.sheet', [$section, $session]));

    $session->refresh();
    expect($session->status)->toBe(ClassSessionStatus::Held);
    $this->assertDatabaseHas('attendance_records', [
        'class_session_id' => $session->id,
        'enrollment_detail_id' => $detail->id,
        'status' => 'present',
    ]);
});

// ---------------------------------------------------------------------------
// RF-03 — Makeup y Advance exentos del guard horario (regresión, sin cambios)
// ---------------------------------------------------------------------------

test('RF-03 (regresión): crear una sesión Makeup fuera de cualquier horario de la sección sigue funcionando', function () {
    // Sección sin ningún Schedule → cualquier "ahora" está, por construcción, fuera de toda
    // ventana horaria real. Sirve para probar que el tipo Makeup está exento sin importar el día.
    ['professor' => $professor, 'section' => $section] = schedGuardProfessorContext();

    $cancelled = ClassSession::factory()->forSection($section)->create([
        'type' => ClassSessionType::Regular,
        'status' => ClassSessionStatus::Cancelled,
        'held_at' => now()->subDay()->format('Y-m-d'),
    ]);

    schedGuardTravelTo(DayOfWeek::Wednesday, '23:00:00');

    $response = $this->actingAs($professor->user)
        ->post(route('professor.sections.attendance.sessions.store', $section), [
            'type' => 'makeup',
            'linked_session_id' => $cancelled->id,
            'topic' => 'Recuperación fuera de horario',
            'held_at' => null,
        ]);

    $response->assertSessionDoesntHaveErrors()
        ->assertRedirect(route('professor.sections.attendance.index', $section));

    $this->assertDatabaseHas('class_sessions', [
        'type' => ClassSessionType::Makeup->value,
        'topic' => 'Recuperación fuera de horario',
        'linked_session_id' => $cancelled->id,
    ]);

    $cancelled->refresh();
    expect($cancelled->status)->toBe(ClassSessionStatus::Recovered);
});

test('RF-03 (regresión): crear una sesión Advance fuera de cualquier horario de la sección sigue funcionando', function () {
    ['professor' => $professor, 'section' => $section] = schedGuardProfessorContext();

    $future = ClassSession::factory()->forSection($section)->create([
        'type' => ClassSessionType::Regular,
        'status' => ClassSessionStatus::Scheduled,
        'held_at' => now()->addWeek()->format('Y-m-d'),
    ]);

    schedGuardTravelTo(DayOfWeek::Wednesday, '23:00:00');

    $response = $this->actingAs($professor->user)
        ->post(route('professor.sections.attendance.sessions.store', $section), [
            'type' => 'advance',
            'linked_session_id' => $future->id,
            'topic' => 'Adelanto fuera de horario',
            'held_at' => null,
        ]);

    $response->assertSessionDoesntHaveErrors()
        ->assertRedirect(route('professor.sections.attendance.index', $section));

    $this->assertDatabaseHas('class_sessions', [
        'type' => ClassSessionType::Advance->value,
        'topic' => 'Adelanto fuera de horario',
        'linked_session_id' => $future->id,
    ]);

    $future->refresh();
    expect($future->status)->toBe(ClassSessionStatus::Advanced);
});

test('RF-03 (regresión): pasar lista en una sesión Makeup/Advance fuera de cualquier horario sigue funcionando', function (ClassSessionType $type) {
    ['professor' => $professor, 'section' => $section] = schedGuardProfessorContext();
    $detail = schedGuardConfirmedDetail($section);

    $session = ClassSession::factory()->forSection($section)->create([
        'type' => $type,
        'status' => ClassSessionStatus::Scheduled,
        'held_at' => null,
    ]);

    schedGuardTravelTo(DayOfWeek::Wednesday, '23:00:00');

    $response = $this->actingAs($professor->user)
        ->put(route('professor.sections.attendance.upsert', [$section, $session]), [
            'marks' => [$detail->id => 'present'],
            'professor_present' => true,
        ]);

    $response->assertRedirect(route('professor.sections.attendance.sheet', [$section, $session]));

    $session->refresh();
    expect($session->status)->toBe(ClassSessionStatus::Held);
})->with([
    'makeup' => [ClassSessionType::Makeup],
    'advance' => [ClassSessionType::Advance],
]);

// ---------------------------------------------------------------------------
// RF-04 — Mismo guard en el flujo Admin (ver nota de Gate::before en el docblock superior)
// ---------------------------------------------------------------------------

test('RF-04: el flujo Admin también rechaza (403) crear una sesión Regular fuera del horario real', function () {
    ['professor' => $professor, 'section' => $section] = schedGuardProfessorContext();

    Schedule::factory()->create([
        'section_id' => $section->id,
        'day_of_week' => DayOfWeek::Monday,
        'start_time' => '08:00:00',
        'end_time' => '08:45:00',
    ]);

    schedGuardTravelTo(DayOfWeek::Tuesday, '10:00:00');

    $response = $this->actingAs($professor->user)
        ->post(route('admin.sections.attendance.sessions.store', $section), [
            'type' => 'regular',
            'topic' => 'Clase fuera de horario (admin)',
            'held_at' => null,
        ]);

    $response->assertForbidden();

    $this->assertDatabaseMissing('class_sessions', ['topic' => 'Clase fuera de horario (admin)']);
    $this->assertDatabaseCount('class_sessions', 0);
});

test('RF-04 (regresión): el flujo Admin permite crear una sesión Regular dentro del horario real', function () {
    ['professor' => $professor, 'section' => $section] = schedGuardProfessorContext();

    Schedule::factory()->create([
        'section_id' => $section->id,
        'day_of_week' => DayOfWeek::Monday,
        'start_time' => '08:00:00',
        'end_time' => '08:45:00',
    ]);

    schedGuardTravelTo(DayOfWeek::Monday, '08:20:00');

    $response = $this->actingAs($professor->user)
        ->post(route('admin.sections.attendance.sessions.store', $section), [
            'type' => 'regular',
            'topic' => 'Clase dentro de horario (admin)',
            'held_at' => null,
        ]);

    $response->assertSessionDoesntHaveErrors()
        ->assertRedirect(route('admin.sections.attendance.index', $section));

    $this->assertDatabaseHas('class_sessions', ['topic' => 'Clase dentro de horario (admin)']);
});

test('RF-04 (arquitectura, no es un bug): un usuario con rol Admin real sigue exento por Gate::before', function () {
    ['section' => $section] = schedGuardProfessorContext();

    $adminUser = User::factory()->create();
    $adminUser->assignRole('Admin');

    Schedule::factory()->create([
        'section_id' => $section->id,
        'day_of_week' => DayOfWeek::Monday,
        'start_time' => '08:00:00',
        'end_time' => '08:45:00',
    ]);

    schedGuardTravelTo(DayOfWeek::Tuesday, '10:00:00');

    $response = $this->actingAs($adminUser)
        ->post(route('admin.sections.attendance.sessions.store', $section), [
            'type' => 'regular',
            'topic' => 'Clase creada por Admin fuera de horario',
            'held_at' => null,
        ]);

    $response->assertSessionDoesntHaveErrors();
    $this->assertDatabaseHas('class_sessions', ['topic' => 'Clase creada por Admin fuera de horario']);
});
