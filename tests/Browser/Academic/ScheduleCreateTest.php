<?php

/**
 * Browser (Dusk) tests — Horarios: Creación (H07–H26)
 *
 * UC cubiertos:
 * - UC-H07: Botón "+ Nuevo horario" visible con permiso, oculto sin él
 * - UC-H08: Clic en botón abre CreateScheduleModal con campos vacíos/por defecto
 * - UC-H09: Crear horario válido → POST → modal cierra → toast "Horario creado." → grilla actualizada
 * - UC-H10: Redirect preserva filtros section_id / period_id activos
 * - UC-H11: valid_until opcional → puede crearse sin ese campo
 * - UC-H12: Seleccionar profesor muestra ProfessorHoursBar
 * - UC-H13: Clic en celda vacía de WeeklyGrid pre-rellena día y hora en modal
 * - UC-H14–H26: Validaciones de campos requeridos y reglas de negocio
 *
 * Run: vendor/bin/sail dusk tests/Browser/Academic/ScheduleCreateTest.php
 */

use App\Enums\SectionType;
use App\Models\Career;
use App\Models\Classroom;
use App\Models\Pensum;
use App\Models\Period;
use App\Models\Professor;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Spatie\Permission\PermissionRegistrar;

uses(DatabaseMigrations::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function adminForCreate(): User
{
    $user = User::factory()->create();
    $user->assignRole('Admin');

    return $user;
}

/**
 * Creates a complete university section with a related subject,
 * professor, classroom, and period. Returns all parts needed for tests.
 *
 * @return array{section: Section, subject: Subject, professor: Professor, classroom: Classroom, period: Period}
 */
function buildScheduleFixture(): array
{
    $career = Career::factory()->create(['name' => 'Carrera Fixture Dusk']);
    $pensum = Pensum::factory()->create(['career_id' => $career->id]);
    $subject = Subject::factory()->create([
        'pensum_id' => $pensum->id,
        'name' => 'Materia Fixture',
        'code' => 'MAT-999',
    ]);
    $period = Period::factory()->semester()->active()->create([
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);
    $section = Section::factory()->create([
        'period_id' => $period->id,
        'subject_id' => $subject->id,
        'type' => SectionType::University,
        'code' => '01',
    ]);
    $professor = Professor::factory()->create(['weekly_hour_limit' => 40]);
    $classroom = Classroom::factory()->create(['identifier' => 'SALA-DUSK']);

    return compact('section', 'subject', 'professor', 'classroom', 'period');
}

/**
 * Sets an input[type=date] or input[type=time] value via JS to ensure Vue reactivity.
 * Date format: YYYY-MM-DD. Time format: HH:MM.
 */
function setInputValue(Browser $browser, string $dusk, string $value): void
{
    $browser->script(
        "var el = document.querySelector('[dusk=\"{$dusk}\"]');"
        ."var nativeInputValueSetter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value').set;"
        ."nativeInputValueSetter.call(el, '{$value}');"
        ."el.dispatchEvent(new Event('input', { bubbles: true }));"
        ."el.dispatchEvent(new Event('change', { bubbles: true }));"
    );
}

// ---------------------------------------------------------------------------
// UC-H07 — Visibilidad del botón
// ---------------------------------------------------------------------------

test('UC-H07a: admin (con permiso schedules.create) ve el botón + Nuevo horario', function () {
    $user = adminForCreate();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            ->assertSee('+ Nuevo horario');
    });
});

test('UC-H07b: usuario sin permiso schedules.create no ve el botón + Nuevo horario', function () {
    // Profesor no tiene schedules.create
    $user = User::factory()->create();
    $user->assignRole('Profesor');

    // Give the professor schedules.view only
    $user->givePermissionTo('schedules.view');

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            ->assertDontSee('+ Nuevo horario');
    });
});

// ---------------------------------------------------------------------------
// UC-H08 — Modal de creación se abre con valores por defecto
// ---------------------------------------------------------------------------

test('UC-H08: clic en + Nuevo horario abre modal con campos por defecto', function () {
    $user = adminForCreate();
    buildScheduleFixture();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            ->click('[dusk="btn-create-schedule"]')
            ->waitForText('Nuevo horario', 5)
            ->assertSee('Nuevo horario')
            ->assertSee('Sección')
            ->assertSee('Materia')
            ->assertSee('Profesor')
            ->assertSee('Aula')
            ->assertSee('Válido desde');
    });
});

// ---------------------------------------------------------------------------
// UC-H09 — Crear horario válido
// ---------------------------------------------------------------------------

test('UC-H09: crear horario válido cierra modal y muestra toast', function () {
    $user = adminForCreate();
    $fixture = buildScheduleFixture();

    $this->browse(function (Browser $browser) use ($user, $fixture) {
        $section = $fixture['section'];
        $subject = $fixture['subject'];
        $professor = $fixture['professor'];
        $classroom = $fixture['classroom'];

        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            ->click('[dusk="btn-create-schedule"]')
            ->waitForText('Nuevo horario', 5)
            ->select('[dusk="create-section-select"]', $section->id)
            ->select('[dusk="create-subject-select"]', $subject->id)
            ->select('[dusk="create-professor-select"]', $professor->id)
            ->select('[dusk="create-classroom-select"]', $classroom->id);

        setInputValue($browser, 'create-start-time', '08:00');
        setInputValue($browser, 'create-end-time', '08:45');
        setInputValue($browser, 'create-valid-from', '2026-01-15');

        $browser->click('[dusk="create-submit-btn"]')
            ->waitForText('Horario creado.', 10)
            ->assertSee('Horario creado.');
    });

    $this->assertDatabaseHas('schedules', [
        'section_id' => $fixture['section']->id,
        'professor_id' => $fixture['professor']->id,
        'classroom_id' => $fixture['classroom']->id,
        'subject_id' => $fixture['subject']->id,
    ]);
});

// ---------------------------------------------------------------------------
// UC-H10 — Redirect preserva filtros activos
// ---------------------------------------------------------------------------

test('UC-H10: redirect tras crear preserva section_id y period_id en URL', function () {
    $user = adminForCreate();
    $fixture = buildScheduleFixture();

    $this->browse(function (Browser $browser) use ($user, $fixture) {
        $section = $fixture['section'];
        $subject = $fixture['subject'];
        $professor = $fixture['professor'];
        $classroom = $fixture['classroom'];
        $period = $fixture['period'];

        $browser->loginAs($user)
            ->visit("/scheduling/schedules?section_id={$section->id}&period_id={$period->id}")
            ->waitForText('Horarios', 10)
            ->click('[dusk="btn-create-schedule"]')
            ->waitForText('Nuevo horario', 5)
            ->select('[dusk="create-section-select"]', $section->id)
            ->select('[dusk="create-subject-select"]', $subject->id)
            ->select('[dusk="create-professor-select"]', $professor->id)
            ->select('[dusk="create-classroom-select"]', $classroom->id);

        setInputValue($browser, 'create-start-time', '09:00');
        setInputValue($browser, 'create-end-time', '09:45');
        setInputValue($browser, 'create-valid-from', '2026-01-15');

        $browser->click('[dusk="create-submit-btn"]')
            ->waitForText('Horario creado.', 10);

        // URL should still contain section_id and period_id
        $currentUrl = $browser->driver->getCurrentURL();
        expect($currentUrl)->toContain("section_id={$section->id}");
        expect($currentUrl)->toContain("period_id={$period->id}");
    });
});

// ---------------------------------------------------------------------------
// UC-H11 — valid_until opcional
// ---------------------------------------------------------------------------

test('UC-H11: crear horario sin valid_until es aceptado', function () {
    $user = adminForCreate();
    $fixture = buildScheduleFixture();

    $this->browse(function (Browser $browser) use ($user, $fixture) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            ->click('[dusk="btn-create-schedule"]')
            ->waitForText('Nuevo horario', 5)
            ->select('[dusk="create-section-select"]', $fixture['section']->id)
            ->select('[dusk="create-subject-select"]', $fixture['subject']->id)
            ->select('[dusk="create-professor-select"]', $fixture['professor']->id)
            ->select('[dusk="create-classroom-select"]', $fixture['classroom']->id);

        setInputValue($browser, 'create-start-time', '10:00');
        setInputValue($browser, 'create-end-time', '10:45');
        setInputValue($browser, 'create-valid-from', '2026-02-01');
        // Leave valid_until empty

        $browser->click('[dusk="create-submit-btn"]')
            ->waitForText('Horario creado.', 10)
            ->assertSee('Horario creado.');
    });

    $this->assertDatabaseHas('schedules', [
        'valid_until' => null,
    ]);
});

// ---------------------------------------------------------------------------
// UC-H12 — ProfessorHoursBar aparece al seleccionar profesor
// ---------------------------------------------------------------------------

test('UC-H12: seleccionar profesor muestra ProfessorHoursBar', function () {
    $user = adminForCreate();
    $fixture = buildScheduleFixture();

    $this->browse(function (Browser $browser) use ($user, $fixture) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            ->click('[dusk="btn-create-schedule"]')
            ->waitForText('Nuevo horario', 5)
            ->select('[dusk="create-professor-select"]', $fixture['professor']->id)
            // ProfessorHoursBar shows: "Carga semanal" and "X.X h de Y h máx"
            ->waitForText('Carga semanal', 5)
            ->assertSee('Carga semanal');
    });
});

// ---------------------------------------------------------------------------
// UC-H13 — Crear desde celda de WeeklyGrid pre-rellena día y hora
// ---------------------------------------------------------------------------

test('UC-H13: clic en celda de la grilla abre modal con el modal de creación', function () {
    $user = adminForCreate();
    buildScheduleFixture();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            // Click the monday column in the grid
            ->click('[dusk="grid-col-monday"]')
            ->waitForText('Nuevo horario', 5)
            ->assertSee('Nuevo horario');

        // The day select should be pre-filled with monday
        $dayValue = $browser->value('[dusk="create-day-select"]');
        expect($dayValue)->toBe('monday');
    });
});

// ---------------------------------------------------------------------------
// UC-H18 — end_time <= start_time → error
// ---------------------------------------------------------------------------

test('UC-H18: end_time igual o anterior a start_time produce error de validación', function () {
    $user = adminForCreate();
    $fixture = buildScheduleFixture();

    $this->browse(function (Browser $browser) use ($user, $fixture) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            ->click('[dusk="btn-create-schedule"]')
            ->waitForText('Nuevo horario', 5)
            ->select('[dusk="create-section-select"]', $fixture['section']->id)
            ->select('[dusk="create-subject-select"]', $fixture['subject']->id)
            ->select('[dusk="create-professor-select"]', $fixture['professor']->id)
            ->select('[dusk="create-classroom-select"]', $fixture['classroom']->id);

        setInputValue($browser, 'create-start-time', '10:00');
        setInputValue($browser, 'create-end-time', '09:00'); // before start
        setInputValue($browser, 'create-valid-from', '2026-01-15');

        $browser->click('[dusk="create-submit-btn"]')
            // Backend error: "The end time field must be a date after start time."
            ->waitForText('must be a date after', 8)
            ->assertSee('must be a date after');
    });
});

// ---------------------------------------------------------------------------
// UC-H19 — valid_until anterior a valid_from → error
// ---------------------------------------------------------------------------

test('UC-H19: valid_until anterior a valid_from produce error de validación', function () {
    $user = adminForCreate();
    $fixture = buildScheduleFixture();

    $this->browse(function (Browser $browser) use ($user, $fixture) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            ->click('[dusk="btn-create-schedule"]')
            ->waitForText('Nuevo horario', 5)
            ->select('[dusk="create-section-select"]', $fixture['section']->id)
            ->select('[dusk="create-subject-select"]', $fixture['subject']->id)
            ->select('[dusk="create-professor-select"]', $fixture['professor']->id)
            ->select('[dusk="create-classroom-select"]', $fixture['classroom']->id);

        setInputValue($browser, 'create-start-time', '08:00');
        setInputValue($browser, 'create-end-time', '08:45');
        setInputValue($browser, 'create-valid-from', '2026-06-01');
        setInputValue($browser, 'create-valid-until', '2026-01-01'); // before valid_from

        $browser->click('[dusk="create-submit-btn"]')
            // Backend error: "The valid until field must be a date after or equal to valid from."
            ->waitForText('must be a date after or equal to', 8)
            ->assertSee('must be a date after or equal to');
    });
});

// ---------------------------------------------------------------------------
// UC-H22 — Sección universitaria: subject_id distinto → 422
// ---------------------------------------------------------------------------

test('UC-H22: subject_id que no corresponde a sección universitaria produce error', function () {
    $user = adminForCreate();
    $fixture = buildScheduleFixture();

    // Create a different subject (different pensum/career)
    $otherSubject = Subject::factory()->create(['name' => 'Materia Ajena']);

    $this->browse(function (Browser $browser) use ($user, $fixture, $otherSubject) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            ->click('[dusk="btn-create-schedule"]')
            ->waitForText('Nuevo horario', 5)
            ->select('[dusk="create-section-select"]', $fixture['section']->id)
            ->select('[dusk="create-subject-select"]', $otherSubject->id)
            ->select('[dusk="create-professor-select"]', $fixture['professor']->id)
            ->select('[dusk="create-classroom-select"]', $fixture['classroom']->id);

        setInputValue($browser, 'create-start-time', '08:00');
        setInputValue($browser, 'create-end-time', '08:45');
        setInputValue($browser, 'create-valid-from', '2026-01-15');

        $browser->click('[dusk="create-submit-btn"]')
            ->waitForText('no corresponde a esta sección universitaria', 8)
            ->assertSee('no corresponde a esta sección universitaria');
    });
});

// ---------------------------------------------------------------------------
// UC-H24 — Sección escolar con main_teacher_id: profesor diferente → 422
// ---------------------------------------------------------------------------

test('UC-H24: sección escolar con main_teacher_id asignado rechaza profesor diferente', function () {
    $user = adminForCreate();

    // Build school section with main_teacher_id
    $pensum = Pensum::factory()->create(['period_type' => 'year', 'total_periods' => 6]);
    $period = Period::factory()->year()->active()->create([
        'start_date' => '2026-01-01',
        'end_date' => '2026-11-30',
    ]);
    $mainProf = Professor::factory()->create();
    $otherProf = Professor::factory()->create();
    $classroom = Classroom::factory()->create(['identifier' => 'AULA-ESCOLAR']);

    $section = Section::factory()->school()->create([
        'period_id' => $period->id,
        'pensum_id' => $pensum->id,
        'main_teacher_id' => $mainProf->id,
        'grade' => 3,
        'letter' => 'A',
        'code' => '3A',
    ]);

    // A subject that belongs to this school pensum via section_subjects
    $subject = Subject::factory()->create(['pensum_id' => $pensum->id]);
    $section->sectionSubjects()->attach($subject->id);

    $this->browse(function (Browser $browser) use ($user, $section, $subject, $otherProf, $classroom) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            ->click('[dusk="btn-create-schedule"]')
            ->waitForText('Nuevo horario', 5)
            ->select('[dusk="create-section-select"]', $section->id)
            ->select('[dusk="create-subject-select"]', $subject->id)
            ->select('[dusk="create-professor-select"]', $otherProf->id)
            ->select('[dusk="create-classroom-select"]', $classroom->id);

        setInputValue($browser, 'create-start-time', '08:00');
        setInputValue($browser, 'create-end-time', '08:45');
        setInputValue($browser, 'create-valid-from', '2026-01-15');

        $browser->click('[dusk="create-submit-btn"]')
            ->waitForText('docente de aula asignado', 8)
            ->assertSee('docente de aula asignado');
    });
});

// ---------------------------------------------------------------------------
// UC-H25 — valid_from anterior al period.start_date → 422
// ---------------------------------------------------------------------------

test('UC-H25: valid_from anterior al period.start_date produce error', function () {
    $user = adminForCreate();
    $fixture = buildScheduleFixture(); // period starts 2026-01-01

    $this->browse(function (Browser $browser) use ($user, $fixture) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            ->click('[dusk="btn-create-schedule"]')
            ->waitForText('Nuevo horario', 5)
            ->select('[dusk="create-section-select"]', $fixture['section']->id)
            ->select('[dusk="create-subject-select"]', $fixture['subject']->id)
            ->select('[dusk="create-professor-select"]', $fixture['professor']->id)
            ->select('[dusk="create-classroom-select"]', $fixture['classroom']->id);

        setInputValue($browser, 'create-start-time', '08:00');
        setInputValue($browser, 'create-end-time', '08:45');
        setInputValue($browser, 'create-valid-from', '2025-12-01'); // before 2026-01-01

        $browser->click('[dusk="create-submit-btn"]')
            ->waitForText('no puede ser anterior al inicio del período', 8)
            ->assertSee('no puede ser anterior al inicio del período');
    });
});

// ---------------------------------------------------------------------------
// UC-H26 — valid_until posterior al period.end_date → 422
// ---------------------------------------------------------------------------

test('UC-H26: valid_until posterior al period.end_date produce error', function () {
    $user = adminForCreate();
    $fixture = buildScheduleFixture(); // period ends 2026-12-31

    $this->browse(function (Browser $browser) use ($user, $fixture) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            ->click('[dusk="btn-create-schedule"]')
            ->waitForText('Nuevo horario', 5)
            ->select('[dusk="create-section-select"]', $fixture['section']->id)
            ->select('[dusk="create-subject-select"]', $fixture['subject']->id)
            ->select('[dusk="create-professor-select"]', $fixture['professor']->id)
            ->select('[dusk="create-classroom-select"]', $fixture['classroom']->id);

        setInputValue($browser, 'create-start-time', '08:00');
        setInputValue($browser, 'create-end-time', '08:45');
        setInputValue($browser, 'create-valid-from', '2026-01-15');
        setInputValue($browser, 'create-valid-until', '2027-03-01'); // after period end

        $browser->click('[dusk="create-submit-btn"]')
            ->waitForText('no puede superar el fin del período', 8)
            ->assertSee('no puede superar el fin del período');
    });
});

// ---------------------------------------------------------------------------
// UC-H16 — start_time < 07:00 → error visible en campo start_time
// ---------------------------------------------------------------------------

test('UC-H16: start_time antes de 07:00 produce error de validación en el campo', function () {
    $user = adminForCreate();
    $fixture = buildScheduleFixture();

    $this->browse(function (Browser $browser) use ($user, $fixture) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            ->click('[dusk="btn-create-schedule"]')
            ->waitForText('Nuevo horario', 5)
            ->select('[dusk="create-section-select"]', $fixture['section']->id)
            ->select('[dusk="create-subject-select"]', $fixture['subject']->id)
            ->select('[dusk="create-professor-select"]', $fixture['professor']->id)
            ->select('[dusk="create-classroom-select"]', $fixture['classroom']->id);

        setInputValue($browser, 'create-start-time', '06:00');
        setInputValue($browser, 'create-end-time', '06:45');
        setInputValue($browser, 'create-valid-from', '2026-01-15');

        $browser->click('[dusk="create-submit-btn"]')
            ->waitForText('07:00', 8)
            ->assertSee('07:00');
    });
});

// ---------------------------------------------------------------------------
// UC-H17 — end_time > 18:00 → error visible en campo end_time
// ---------------------------------------------------------------------------

test('UC-H17: end_time después de 18:00 produce error de validación en el campo', function () {
    $user = adminForCreate();
    $fixture = buildScheduleFixture();

    $this->browse(function (Browser $browser) use ($user, $fixture) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            ->click('[dusk="btn-create-schedule"]')
            ->waitForText('Nuevo horario', 5)
            ->select('[dusk="create-section-select"]', $fixture['section']->id)
            ->select('[dusk="create-subject-select"]', $fixture['subject']->id)
            ->select('[dusk="create-professor-select"]', $fixture['professor']->id)
            ->select('[dusk="create-classroom-select"]', $fixture['classroom']->id);

        setInputValue($browser, 'create-start-time', '17:00');
        setInputValue($browser, 'create-end-time', '18:15');
        setInputValue($browser, 'create-valid-from', '2026-01-15');

        $browser->click('[dusk="create-submit-btn"]')
            ->waitForText('18:00', 8)
            ->assertSee('18:00');
    });
});

// ---------------------------------------------------------------------------
// UC-H18b — límites exactos 07:00 y 18:00 son válidos (inclusivos)
// ---------------------------------------------------------------------------

test('UC-H18b: start_time 07:00 y end_time 18:00 son aceptados como válidos', function () {
    $user = adminForCreate();
    $fixture = buildScheduleFixture();

    $this->browse(function (Browser $browser) use ($user, $fixture) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            ->click('[dusk="btn-create-schedule"]')
            ->waitForText('Nuevo horario', 5)
            ->select('[dusk="create-section-select"]', $fixture['section']->id)
            ->select('[dusk="create-subject-select"]', $fixture['subject']->id)
            ->select('[dusk="create-professor-select"]', $fixture['professor']->id)
            ->select('[dusk="create-classroom-select"]', $fixture['classroom']->id);

        setInputValue($browser, 'create-start-time', '07:00');
        setInputValue($browser, 'create-end-time', '18:00');
        setInputValue($browser, 'create-valid-from', '2026-01-15');

        $browser->click('[dusk="create-submit-btn"]')
            ->waitForText('Horario creado.', 10);
    });
});
