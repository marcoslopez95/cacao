<?php

/**
 * Browser (Dusk) tests — Horarios: Edición (H33–H38)
 *
 * UC cubiertos:
 * - UC-H33: Controles de editar visibles con permiso, ocultos sin él
 * - UC-H34: Clic en editar abre EditScheduleModal pre-llenado con valores actuales
 * - UC-H35: Editar y guardar válido → PATCH → toast "Horario actualizado." → grilla actualizada
 * - UC-H36: Guardar sin cambios → no produce errores (conflictos excluyen el propio id)
 * - UC-H37: Las mismas validaciones de creación aplican en edición
 * - UC-H38: Sin permiso schedules.update → PATCH directo devuelve 403
 *
 * Run: vendor/bin/sail dusk tests/Browser/Academic/ScheduleEditTest.php
 */

use App\Enums\DayOfWeek;
use App\Enums\SectionType;
use App\Models\Career;
use App\Models\Classroom;
use App\Models\Pensum;
use App\Models\Period;
use App\Models\Professor;
use App\Models\Schedule;
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

/**
 * Sets a date or time input value using the native input value setter
 * so that Vue's v-model reactivity is triggered correctly.
 */
function setInputValueEdit(Browser $browser, string $dusk, string $value): void
{
    $browser->script(
        "var el = document.querySelector('[dusk=\"{$dusk}\"]');"
        ."var nativeInputValueSetter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value').set;"
        ."nativeInputValueSetter.call(el, '{$value}');"
        ."el.dispatchEvent(new Event('input', { bubbles: true }));"
        ."el.dispatchEvent(new Event('change', { bubbles: true }));"
    );
}

function adminForEdit(): User
{
    $user = User::factory()->create();
    $user->assignRole('Admin');

    return $user;
}

/**
 * Builds a complete schedule fixture ready for editing.
 *
 * @return array{schedule: Schedule, section: Section, subject: Subject, professor: Professor, classroom: Classroom, period: Period}
 */
function buildEditableSchedule(): array
{
    $career = Career::factory()->create(['name' => 'Carrera Edit Dusk']);
    $pensum = Pensum::factory()->create(['career_id' => $career->id]);
    $subject = Subject::factory()->create([
        'pensum_id' => $pensum->id,
        'name' => 'Materia Edit Dusk',
        'code' => 'EDT-001',
    ]);
    $period = Period::factory()->semester()->active()->create([
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);
    $section = Section::factory()->create([
        'period_id' => $period->id,
        'subject_id' => $subject->id,
        'type' => SectionType::University,
        'code' => 'E01',
    ]);
    $professor = Professor::factory()->create(['weekly_hour_limit' => 40]);
    $classroom = Classroom::factory()->create(['identifier' => 'SALA-EDT']);

    $schedule = Schedule::factory()->create([
        'section_id' => $section->id,
        'professor_id' => $professor->id,
        'classroom_id' => $classroom->id,
        'subject_id' => $subject->id,
        'day_of_week' => DayOfWeek::Monday,
        'start_time' => '08:00:00',
        'end_time' => '08:45:00',
        'type' => 'theory',
        'valid_from' => '2026-01-15',
        'valid_until' => null,
    ]);

    return compact('schedule', 'section', 'subject', 'professor', 'classroom', 'period');
}

// ---------------------------------------------------------------------------
// UC-H33 — Visibilidad de controles de editar
// ---------------------------------------------------------------------------

test('UC-H33a: admin ve controles de editar en la vista lista', function () {
    $user = adminForEdit();
    buildEditableSchedule();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            // Switch to list view to see edit controls
            ->click('[dusk="btn-view-list"]')
            ->waitFor('.sch-list', 5)
            ->assertPresent('[dusk="list-edit-btn"]');
    });
});

test('UC-H33b: usuario sin permiso schedules.update no ve controles de editar', function () {
    $user = User::factory()->create();
    $user->assignRole('Profesor');
    $user->givePermissionTo('schedules.view');
    buildEditableSchedule();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            ->click('[dusk="btn-view-list"]')
            ->waitFor('.sch-list', 5)
            ->assertMissing('[dusk="list-edit-btn"]');
    });
});

// ---------------------------------------------------------------------------
// UC-H34 — Modal de edición pre-llenado
// ---------------------------------------------------------------------------

test('UC-H34: clic en editar abre modal con todos los campos pre-llenados', function () {
    $user = adminForEdit();
    $fixture = buildEditableSchedule();

    $this->browse(function (Browser $browser) use ($user, $fixture) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            ->click('[dusk="btn-view-list"]')
            ->waitFor('.sch-list', 5)
            ->click('[dusk="list-edit-btn"]')
            ->waitForText('Editar horario', 5)
            ->assertSee('Editar horario');

        // Verify section select has the expected value
        $sectionValue = $browser->value('[dusk="edit-section-select"]');
        expect((int) $sectionValue)->toBe($fixture['section']->id);

        // Verify subject value
        $subjectValue = $browser->value('[dusk="edit-subject-select"]');
        expect((int) $subjectValue)->toBe($fixture['subject']->id);

        // Verify professor value
        $profValue = $browser->value('[dusk="edit-professor-select"]');
        expect((int) $profValue)->toBe($fixture['professor']->id);
    });
});

// ---------------------------------------------------------------------------
// UC-H35 — Editar y guardar válido
// ---------------------------------------------------------------------------

test('UC-H35: editar y guardar horario válido muestra toast Horario actualizado.', function () {
    $user = adminForEdit();
    $fixture = buildEditableSchedule();

    // Create a second classroom for the edit
    $newClassroom = Classroom::factory()->create(['identifier' => 'SALA-NUEVO']);

    $this->browse(function (Browser $browser) use ($user, $newClassroom) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            ->click('[dusk="btn-view-list"]')
            ->waitFor('.sch-list', 5)
            ->click('[dusk="list-edit-btn"]')
            ->waitForText('Editar horario', 5)
            // Change classroom
            ->select('[dusk="edit-classroom-select"]', $newClassroom->id)
            ->click('[dusk="edit-submit-btn"]')
            ->waitForText('Horario actualizado.', 8)
            ->assertSee('Horario actualizado.');
    });

    $this->assertDatabaseHas('schedules', [
        'id' => $fixture['schedule']->id,
        'classroom_id' => $newClassroom->id,
    ]);
});

// ---------------------------------------------------------------------------
// UC-H36 — Guardar sin cambios no produce errores
// ---------------------------------------------------------------------------

test('UC-H36: guardar horario sin cambios no produce errores de validación', function () {
    $user = adminForEdit();
    buildEditableSchedule();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            ->click('[dusk="btn-view-list"]')
            ->waitFor('.sch-list', 5)
            ->click('[dusk="list-edit-btn"]')
            ->waitForText('Editar horario', 5)
            // Submit without changing anything
            ->click('[dusk="edit-submit-btn"]')
            ->waitForText('Horario actualizado.', 8)
            ->assertSee('Horario actualizado.')
            ->assertDontSee('error');
    });
});

// ---------------------------------------------------------------------------
// UC-H37 — Validaciones de creación aplican en edición (end_time <= start_time)
// ---------------------------------------------------------------------------

test('UC-H37: validación end_time anterior a start_time aplica en edición', function () {
    $user = adminForEdit();
    buildEditableSchedule();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            ->click('[dusk="btn-view-list"]')
            ->waitFor('.sch-list', 5)
            ->click('[dusk="list-edit-btn"]')
            ->waitForText('Editar horario', 5);

        setInputValueEdit($browser, 'edit-start-time', '10:00');
        setInputValueEdit($browser, 'edit-end-time', '09:00'); // invalid: end before start

        $browser->click('[dusk="edit-submit-btn"]')
            ->waitForText('must be a date after', 8);
    });
});

// ---------------------------------------------------------------------------
// UC-H38 — Sin permiso schedules.update → 403
// ---------------------------------------------------------------------------

test('UC-H38: usuario sin permiso schedules.update recibe 403 en PATCH directo', function () {
    $fixture = buildEditableSchedule();

    $user = User::factory()->create();
    $user->assignRole('Estudiante');

    // Use withoutMiddleware to bypass CSRF in this HTTP assertion (Dusk test context)
    $this->withoutMiddleware()
        ->actingAs($user)
        ->patch(route('scheduling.schedules.update', $fixture['schedule']))
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// UC-H19 — ProfessorHoursBar aparece en EditScheduleModal al seleccionar profesor
// ---------------------------------------------------------------------------

test('UC-H19: EditScheduleModal muestra ProfessorHoursBar al abrir con profesor pre-seleccionado', function () {
    $user = adminForEdit();
    $fixture = buildEditableSchedule();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            ->click('[dusk="btn-view-list"]')
            ->waitFor('.sch-list', 5)
            ->click('[dusk="list-edit-btn"]')
            ->waitForText('Editar horario', 5);

        $browser->assertPresent('[dusk="professor-hours-bar"]');
    });
});
