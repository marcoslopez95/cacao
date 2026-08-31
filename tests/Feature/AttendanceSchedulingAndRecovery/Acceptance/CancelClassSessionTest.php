<?php

/**
 * Acceptance tests — HLZ-46 (feature 20-attendance-scheduling-and-recovery)
 *
 * Contrato: existe una acción de cancelar sesión (`CancelClassSessionAction`) que transiciona
 * `status` de `scheduled`/`held` a `cancelled`, expuesta vía
 * `PATCH .../attendance/sessions/{classSession}/cancel` tanto en Profesor como en Admin.
 *
 * Cubre specs/20-attendance-scheduling-and-recovery/requirements.md:
 * - RF-05: existe una acción de cancelar sesión: transiciona `scheduled`/`held` a `cancelled`.
 * - RF-06: cancelar una sesión `cancelled`/`advanced`/`recovered` responde 422, sin cambiar estado.
 * - RF-07: cancelar una sesión `held` no borra sus `AttendanceRecord` existentes.
 * - RF-08: tanto el profesor dueño de la sección como el Admin pueden cancelar (mismo patrón
 *          de autorización que el resto del módulo).
 * - RF-09: una vez cancelada, la sesión es candidata válida para Recuperación end-to-end.
 *
 * Intocable por el implementer — define el contrato antes del fix. En el momento de escribir
 * este archivo:
 * - `App\Actions\Attendance\CancelClassSessionAction` NO EXISTE (clase inexistente) → los tests
 *   a nivel de Action fallan con un error de clase no encontrada.
 * - La ruta `PATCH .../attendance/sessions/{classSession}/cancel` NO EXISTE en `routes/web.php`
 *   (ni en el grupo `professor.` ni en `admin.`) → los tests HTTP fallan con 404.
 * Todos los tests de este archivo deben estar en ROJO por estas dos razones hasta que se
 * implemente el fix completo (Action + rutas + controllers), tal como lo describe
 * specs/20-attendance-scheduling-and-recovery/design.md.
 *
 * No se usan rutas nombradas para el endpoint de cancelar (`professorCancelUrl()` /
 * `adminCancelUrl()` construyen el path literal descrito en design.md) precisamente porque el
 * nombre de ruta (`sections.attendance.sessions.cancel`) tampoco existe todavía — usar
 * `route()` haría fallar la suite entera al cargar el archivo en vez de fallar cada test
 * individualmente con una razón clara (404).
 */

use App\Actions\Attendance\CancelClassSessionAction;
use App\Enums\ClassSessionStatus;
use App\Enums\ClassSessionType;
use App\Models\AttendanceRecord;
use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\EnrollmentDetail;
use App\Models\Period;
use App\Models\Professor;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Profesor dueño de una sección con período activo — contexto base para los tests.
 *
 * @return array{professor: Professor, section: Section}
 */
function cancelCtxProfessorSection(): array
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
 * Crea un EnrollmentDetail confirmado para la sección.
 */
function cancelCtxConfirmedDetail(Section $section): EnrollmentDetail
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

function cancelCtxProfessorUrl(Section $section, ClassSession $classSession): string
{
    return "/professor/sections/{$section->id}/attendance/sessions/{$classSession->id}/cancel";
}

function cancelCtxAdminUrl(Section $section, ClassSession $classSession): string
{
    return "/admin/sections/{$section->id}/attendance/sessions/{$classSession->id}/cancel";
}

// ---------------------------------------------------------------------------
// RF-05 / RF-07 — CancelClassSessionAction a nivel unitario
// ---------------------------------------------------------------------------

test('RF-05: CancelClassSessionAction transiciona una sesión scheduled a cancelled', function () {
    $section = Section::factory()->create();
    $session = ClassSession::factory()->forSection($section)->create([
        'status' => ClassSessionStatus::Scheduled,
    ]);

    $result = (new CancelClassSessionAction)->handle($session);

    expect($result->status)->toBe(ClassSessionStatus::Cancelled);
    $this->assertDatabaseHas('class_sessions', [
        'id' => $session->id,
        'status' => ClassSessionStatus::Cancelled->value,
    ]);
});

test('RF-05/RF-07: CancelClassSessionAction transiciona una sesión held a cancelled preservando sus AttendanceRecord', function () {
    $section = Section::factory()->create();
    $session = ClassSession::factory()->forSection($section)->create([
        'status' => ClassSessionStatus::Held,
    ]);
    $detailA = cancelCtxConfirmedDetail($section);
    $detailB = cancelCtxConfirmedDetail($section);

    $recordA = AttendanceRecord::create([
        'class_session_id' => $session->id,
        'enrollment_detail_id' => $detailA->id,
        'status' => 'present',
    ]);
    $recordB = AttendanceRecord::create([
        'class_session_id' => $session->id,
        'enrollment_detail_id' => $detailB->id,
        'status' => 'absent',
    ]);

    (new CancelClassSessionAction)->handle($session);

    $session->refresh();
    expect($session->status)->toBe(ClassSessionStatus::Cancelled);

    $this->assertDatabaseHas('attendance_records', [
        'id' => $recordA->id,
        'class_session_id' => $session->id,
        'enrollment_detail_id' => $detailA->id,
        'status' => 'present',
    ]);
    $this->assertDatabaseHas('attendance_records', [
        'id' => $recordB->id,
        'class_session_id' => $session->id,
        'enrollment_detail_id' => $detailB->id,
        'status' => 'absent',
    ]);
    $this->assertDatabaseCount('attendance_records', 2);
});

test('RF-06: CancelClassSessionAction rechaza cancelar una sesión ya cancelled/advanced/recovered', function (ClassSessionStatus $status) {
    $section = Section::factory()->create();
    $session = ClassSession::factory()->forSection($section)->create(['status' => $status]);

    try {
        (new CancelClassSessionAction)->handle($session);
        test()->fail('Se esperaba ValidationException por sesión no cancelable.');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('status')
            ->and($e->errors()['status'][0])->toBe('Esta sesión no se puede cancelar.');
    }

    $session->refresh();
    expect($session->status)->toBe($status);
})->with([
    'cancelled' => [ClassSessionStatus::Cancelled],
    'advanced' => [ClassSessionStatus::Advanced],
    'recovered' => [ClassSessionStatus::Recovered],
]);

// ---------------------------------------------------------------------------
// RF-05 / RF-08 — Endpoint HTTP, Profesor
// ---------------------------------------------------------------------------

test('RF-05/RF-08: el profesor dueño de la sección puede cancelar una sesión scheduled vía HTTP', function () {
    ['professor' => $professor, 'section' => $section] = cancelCtxProfessorSection();
    $session = ClassSession::factory()->forSection($section)->create([
        'status' => ClassSessionStatus::Scheduled,
    ]);

    $response = $this->actingAs($professor->user)
        ->patch(cancelCtxProfessorUrl($section, $session));

    $response->assertRedirect(route('professor.sections.attendance.index', $section));

    $session->refresh();
    expect($session->status)->toBe(ClassSessionStatus::Cancelled);
});

test('RF-08: un profesor que no es dueño de la sección no puede cancelar la sesión (403)', function () {
    ['section' => $section] = cancelCtxProfessorSection();
    $otherProfessor = Professor::factory()->create();
    $session = ClassSession::factory()->forSection($section)->create([
        'status' => ClassSessionStatus::Scheduled,
    ]);

    $response = $this->actingAs($otherProfessor->user)
        ->patch(cancelCtxProfessorUrl($section, $session));

    $response->assertForbidden();

    $session->refresh();
    expect($session->status)->toBe(ClassSessionStatus::Scheduled);
});

test('RF-06 (HTTP, profesor): cancelar una sesión ya cancelled/advanced/recovered responde 422 con el mensaje exacto', function (ClassSessionStatus $status) {
    ['professor' => $professor, 'section' => $section] = cancelCtxProfessorSection();
    $session = ClassSession::factory()->forSection($section)->create(['status' => $status]);

    $response = $this->actingAs($professor->user)
        ->patchJson(cancelCtxProfessorUrl($section, $session));

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['status' => 'Esta sesión no se puede cancelar.']);

    $session->refresh();
    expect($session->status)->toBe($status);
})->with([
    'cancelled' => [ClassSessionStatus::Cancelled],
    'advanced' => [ClassSessionStatus::Advanced],
    'recovered' => [ClassSessionStatus::Recovered],
]);

// ---------------------------------------------------------------------------
// RF-08 — Endpoint HTTP, Admin
// ---------------------------------------------------------------------------

test('RF-08: el flujo Admin también permite cancelar una sesión held (mismo profesor dueño, ruta admin)', function () {
    ['professor' => $professor, 'section' => $section] = cancelCtxProfessorSection();
    $session = ClassSession::factory()->forSection($section)->create([
        'status' => ClassSessionStatus::Held,
    ]);
    $detail = cancelCtxConfirmedDetail($section);
    AttendanceRecord::create([
        'class_session_id' => $session->id,
        'enrollment_detail_id' => $detail->id,
        'status' => 'present',
    ]);

    $response = $this->actingAs($professor->user)
        ->patch(cancelCtxAdminUrl($section, $session));

    $response->assertRedirect(route('admin.sections.attendance.index', $section));

    $session->refresh();
    expect($session->status)->toBe(ClassSessionStatus::Cancelled);

    $this->assertDatabaseHas('attendance_records', [
        'class_session_id' => $session->id,
        'enrollment_detail_id' => $detail->id,
        'status' => 'present',
    ]);
    $this->assertDatabaseCount('attendance_records', 1);
});

// ---------------------------------------------------------------------------
// RF-09 — Recuperación de punta a punta contra una sesión recién cancelada
// ---------------------------------------------------------------------------

test('RF-09: recuperación de punta a punta vía HTTP contra una sesión recién cancelada', function () {
    ['professor' => $professor, 'section' => $section] = cancelCtxProfessorSection();
    $detailA = cancelCtxConfirmedDetail($section);
    $detailB = cancelCtxConfirmedDetail($section);

    $target = ClassSession::factory()->forSection($section)->create([
        'type' => ClassSessionType::Regular,
        'status' => ClassSessionStatus::Scheduled,
    ]);

    // Paso 1: cancelar la sesión objetivo.
    $this->actingAs($professor->user)
        ->patch(cancelCtxProfessorUrl($section, $target))
        ->assertRedirect(route('professor.sections.attendance.index', $section));

    $target->refresh();
    expect($target->status)->toBe(ClassSessionStatus::Cancelled);

    // Paso 2: crear una Recuperación (makeup) vinculada a la sesión recién cancelada.
    $storeResponse = $this->actingAs($professor->user)
        ->post(route('professor.sections.attendance.sessions.store', $section), [
            'type' => 'makeup',
            'linked_session_id' => $target->id,
            'topic' => 'Recuperación end-to-end',
            'held_at' => null,
        ]);

    $storeResponse->assertSessionDoesntHaveErrors()
        ->assertRedirect(route('professor.sections.attendance.index', $section));

    $makeup = ClassSession::where('section_id', $section->id)
        ->where('type', ClassSessionType::Makeup)
        ->first();

    expect($makeup)->not->toBeNull()
        ->and($makeup->linked_session_id)->toBe($target->id);

    $target->refresh();
    expect($target->status)->toBe(ClassSessionStatus::Recovered);

    // Paso 3: pasar lista en la Recuperación.
    $upsertResponse = $this->actingAs($professor->user)
        ->put(route('professor.sections.attendance.upsert', [$section, $makeup]), [
            'marks' => [
                $detailA->id => 'present',
                $detailB->id => 'absent',
            ],
            'professor_present' => true,
        ]);

    $upsertResponse->assertRedirect(route('professor.sections.attendance.sheet', [$section, $makeup]));

    $makeup->refresh();
    expect($makeup->status)->toBe(ClassSessionStatus::Held);

    $this->assertDatabaseHas('attendance_records', [
        'class_session_id' => $makeup->id,
        'enrollment_detail_id' => $detailA->id,
        'status' => 'present',
    ]);
    $this->assertDatabaseHas('attendance_records', [
        'class_session_id' => $makeup->id,
        'enrollment_detail_id' => $detailB->id,
        'status' => 'absent',
    ]);
    $this->assertDatabaseCount('attendance_records', 2);

    // La sesión objetivo cancelada permanece en su estado terminal `recovered` — la asistencia
    // de la Recuperación no se copia hacia ella (solo Adelanto copia registros al vinculado).
    $target->refresh();
    expect($target->status)->toBe(ClassSessionStatus::Recovered);
});
