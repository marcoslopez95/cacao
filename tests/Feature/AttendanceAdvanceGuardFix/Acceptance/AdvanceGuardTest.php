<?php

/**
 * Acceptance tests — HLZ-45 (feature 19-attendance-advance-guard-fix)
 *
 * Contrato: `CreateAdvanceSessionAction::handle()` debe rechazar la creación de un segundo
 * "adelanto" (advance) vinculado a una sesión que ya tiene `status = advanced`, con el mismo
 * patrón de guard que ya existe en `CreateMakeupSessionAction` para `status = recovered`.
 *
 * Cubre specs/19-attendance-advance-guard-fix/requirements.md:
 * - RF-01: POST .../attendance/sessions con type=advance + linked_session_id ya `advanced`
 *          responde con error de validación, nunca 200/201.
 * - RF-02: el mensaje es exactamente "Esta sesión ya fue adelantada."
 * - RF-03: crear un adelanto vinculado a una sesión `scheduled` sigue funcionando (regresión).
 * - RF-04: el guard aplica tanto si llega vía Action directa (equivalente a "bypass UI": el
 *          selector de la UI ya filtra, pero el backend no depende de ese filtro) como si llega
 *          vía el endpoint HTTP con el linked_session_id enviado directamente.
 *
 * Intocable por el implementer — define el contrato antes del fix. En el momento de escribir
 * este archivo, `CreateAdvanceSessionAction::handle()` NO tiene el guard (ver
 * app/Actions/Attendance/CreateAdvanceSessionAction.php), así que los tests de RF-01/RF-02/RF-04
 * deben estar en ROJO. El de RF-03 (regresión) debe estar en VERDE desde ya, porque no depende
 * del fix — es el comportamiento actual, correcto, que no debe romperse.
 *
 * Nota sobre el status HTTP (RF-01): este endpoint es un form Inertia normal (ver
 * ProfessorAttendanceTest::'storeSession validates required type field', que solo verifica
 * `assertSessionHasErrors` sin fijar código de estado — el flujo Inertia real redirige 302 con
 * errores en sesión). Para satisfacer literalmente "responde con error de validación (422)" de
 * RF-01, el primer test HTTP usa `postJson()` (fuerza `expectsJson()` en el ValidationException
 * handler de Laravel → 422 JSON). El segundo test HTTP usa `post()` normal (flujo real de
 * formulario/Inertia) para probar RF-04 con el transporte que realmente usa la UI, confirmando
 * que el guard no depende del formato de la petición ni de ningún filtro de presentación.
 */

use App\Actions\Attendance\CreateAdvanceSessionAction;
use App\Enums\ClassSessionStatus;
use App\Enums\ClassSessionType;
use App\Http\Wrappers\Attendance\ClassSessionWrapper;
use App\Models\ClassSession;
use App\Models\Period;
use App\Models\Professor;
use App\Models\Section;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

/**
 * Profesor dueño de una sección con período activo — contexto base para los tests HTTP.
 *
 * @return array{professor: Professor, section: Section}
 */
function advanceGuardHttpContext(): array
{
    $professor = Professor::factory()->create();
    $period = Period::factory()->active()->create();
    $section = Section::factory()->create([
        'period_id' => $period->id,
        'main_teacher_id' => $professor->id,
    ]);

    return compact('professor', 'section');
}

// ---------------------------------------------------------------------------
// RF-01 / RF-02 / RF-04 — Guard a nivel de Action (el más directo: prueba que la
// validación vive en el backend, no en el filtro de presentación de la UI)
// ---------------------------------------------------------------------------

test('RF-01/RF-02: CreateAdvanceSessionAction rechaza un linked_session_id ya advanced', function () {
    $section = Section::factory()->create();

    $firstAdvance = ClassSession::factory()->forSection($section)->create([
        'type' => ClassSessionType::Advance,
        'status' => ClassSessionStatus::Scheduled,
    ]);

    $alreadyAdvanced = ClassSession::factory()->forSection($section)->create([
        'type' => ClassSessionType::Regular,
        'status' => ClassSessionStatus::Advanced,
        'linked_session_id' => $firstAdvance->id,
    ]);

    $wrapper = new ClassSessionWrapper([
        'section_id' => $section->id,
        'type' => ClassSessionType::Advance->value,
        'linked_session_id' => $alreadyAdvanced->id,
        'topic' => 'Segundo adelanto sobre la misma sesión',
    ]);

    $action = new CreateAdvanceSessionAction;

    try {
        $action->handle($wrapper);
        test()->fail('Se esperaba ValidationException por sesión ya adelantada.');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('linked_session_id')
            ->and($e->errors()['linked_session_id'][0])->toBe('Esta sesión ya fue adelantada.');
    }

    // No se crea ningún ClassSession nuevo por el intento fallido (siguen las 2 preexistentes).
    $this->assertDatabaseCount('class_sessions', 2);

    // La sesión objetivo no cambia: sigue advanced, apuntando al PRIMER adelanto.
    $this->assertDatabaseHas('class_sessions', [
        'id' => $alreadyAdvanced->id,
        'status' => ClassSessionStatus::Advanced->value,
        'linked_session_id' => $firstAdvance->id,
    ]);
});

// ---------------------------------------------------------------------------
// RF-03 — Regresión: adelanto vinculado a una sesión `scheduled` sigue funcionando
// ---------------------------------------------------------------------------

test('RF-03 (regresión): CreateAdvanceSessionAction sigue funcionando para una sesión scheduled', function () {
    $section = Section::factory()->create();

    $futureSession = ClassSession::factory()->forSection($section)->create([
        'type' => ClassSessionType::Regular,
        'status' => ClassSessionStatus::Scheduled,
    ]);

    $wrapper = new ClassSessionWrapper([
        'section_id' => $section->id,
        'type' => ClassSessionType::Advance->value,
        'linked_session_id' => $futureSession->id,
        'topic' => 'Clase adelantada — regresión RF-03',
    ]);

    $action = new CreateAdvanceSessionAction;
    $advanceSession = $action->handle($wrapper);

    expect($advanceSession)->toBeInstanceOf(ClassSession::class)
        ->and($advanceSession->exists)->toBeTrue()
        ->and($advanceSession->type)->toBe(ClassSessionType::Advance)
        ->and($advanceSession->status)->toBe(ClassSessionStatus::Scheduled)
        ->and($advanceSession->linked_session_id)->toBe($futureSession->id);

    $this->assertDatabaseHas('class_sessions', [
        'id' => $advanceSession->id,
        'section_id' => $section->id,
        'type' => ClassSessionType::Advance->value,
        'linked_session_id' => $futureSession->id,
    ]);

    $futureSession->refresh();
    expect($futureSession->status)->toBe(ClassSessionStatus::Advanced)
        ->and($futureSession->linked_session_id)->toBe($advanceSession->id);

    $this->assertDatabaseHas('class_sessions', [
        'id' => $futureSession->id,
        'status' => ClassSessionStatus::Advanced->value,
        'linked_session_id' => $advanceSession->id,
    ]);

    $this->assertDatabaseCount('class_sessions', 2);
});

// ---------------------------------------------------------------------------
// RF-01 / RF-02 — Endpoint HTTP (JSON): 422 explícito con el mensaje exacto
// ---------------------------------------------------------------------------

test('RF-01/RF-02: POST del endpoint responde 422 con el mensaje exacto cuando la sesión ya fue adelantada', function () {
    ['professor' => $professor, 'section' => $section] = advanceGuardHttpContext();

    $firstAdvance = ClassSession::factory()->forSection($section)->create([
        'type' => ClassSessionType::Advance,
        'status' => ClassSessionStatus::Scheduled,
    ]);

    $alreadyAdvanced = ClassSession::factory()->forSection($section)->create([
        'type' => ClassSessionType::Regular,
        'status' => ClassSessionStatus::Advanced,
        'linked_session_id' => $firstAdvance->id,
    ]);

    $response = $this->actingAs($professor->user)
        ->postJson(route('professor.sections.attendance.sessions.store', $section), [
            'type' => 'advance',
            'linked_session_id' => $alreadyAdvanced->id,
            'topic' => 'Segundo intento de adelanto (JSON)',
            'held_at' => null,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['linked_session_id' => 'Esta sesión ya fue adelantada.']);

    $this->assertDatabaseMissing('class_sessions', [
        'topic' => 'Segundo intento de adelanto (JSON)',
    ]);

    $this->assertDatabaseCount('class_sessions', 2);

    $this->assertDatabaseHas('class_sessions', [
        'id' => $alreadyAdvanced->id,
        'status' => ClassSessionStatus::Advanced->value,
        'linked_session_id' => $firstAdvance->id,
    ]);
});

// ---------------------------------------------------------------------------
// RF-04 — El guard aplica igual con el transporte real de la UI (form/Inertia post,
// bypass del selector: se envía linked_session_id directo, como si viniera de un
// cliente HTTP que no pasó por el <select> que ya filtra sesiones `advanced`)
// ---------------------------------------------------------------------------

test('RF-04: POST normal (form/Inertia) también rechaza el linked_session_id enviado directo, bypass del selector de UI', function () {
    ['professor' => $professor, 'section' => $section] = advanceGuardHttpContext();

    $firstAdvance = ClassSession::factory()->forSection($section)->create([
        'type' => ClassSessionType::Advance,
        'status' => ClassSessionStatus::Scheduled,
    ]);

    $alreadyAdvanced = ClassSession::factory()->forSection($section)->create([
        'type' => ClassSessionType::Regular,
        'status' => ClassSessionStatus::Advanced,
        'linked_session_id' => $firstAdvance->id,
    ]);

    $response = $this->actingAs($professor->user)
        ->post(route('professor.sections.attendance.sessions.store', $section), [
            'type' => 'advance',
            'linked_session_id' => $alreadyAdvanced->id,
            'topic' => 'Segundo intento de adelanto (bypass UI)',
            'held_at' => null,
        ]);

    $response->assertSessionHasErrors(['linked_session_id' => 'Esta sesión ya fue adelantada.']);

    $this->assertDatabaseMissing('class_sessions', [
        'topic' => 'Segundo intento de adelanto (bypass UI)',
    ]);

    $this->assertDatabaseCount('class_sessions', 2);

    $this->assertDatabaseHas('class_sessions', [
        'id' => $alreadyAdvanced->id,
        'status' => ClassSessionStatus::Advanced->value,
        'linked_session_id' => $firstAdvance->id,
    ]);
});

// ---------------------------------------------------------------------------
// RF-03 — Regresión a nivel HTTP: el flujo normal (sesión scheduled) sigue igual
// ---------------------------------------------------------------------------

test('RF-03 (regresión, HTTP): profesor sigue pudiendo crear un adelanto vinculado a una sesión scheduled', function () {
    ['professor' => $professor, 'section' => $section] = advanceGuardHttpContext();

    $futureSession = ClassSession::factory()->forSection($section)->create([
        'type' => ClassSessionType::Regular,
        'status' => ClassSessionStatus::Scheduled,
    ]);

    $response = $this->actingAs($professor->user)
        ->post(route('professor.sections.attendance.sessions.store', $section), [
            'type' => 'advance',
            'linked_session_id' => $futureSession->id,
            'topic' => 'Adelanto normal — regresión RF-03 HTTP',
            'held_at' => null,
        ]);

    $response->assertSessionDoesntHaveErrors()
        ->assertRedirect(route('professor.sections.attendance.index', $section));

    $this->assertDatabaseHas('class_sessions', [
        'section_id' => $section->id,
        'type' => ClassSessionType::Advance->value,
        'topic' => 'Adelanto normal — regresión RF-03 HTTP',
        'linked_session_id' => $futureSession->id,
    ]);

    $this->assertDatabaseHas('class_sessions', [
        'id' => $futureSession->id,
        'status' => ClassSessionStatus::Advanced->value,
    ]);

    $this->assertDatabaseCount('class_sessions', 2);
});
