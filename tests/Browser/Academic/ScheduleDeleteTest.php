<?php

/**
 * Browser (Dusk) tests — Horarios: Eliminación (H39–H43)
 *
 * UC cubiertos:
 * - UC-H39: Controles de eliminar visibles con permiso, ocultos sin él
 * - UC-H40: Clic en eliminar abre DeleteScheduleModal con datos del horario
 * - UC-H41: Confirmar eliminación → DELETE → toast "Horario eliminado." → evento desaparece
 * - UC-H42: Cancelar en modal → no hace request, modal cierra, grilla sin cambios
 * - UC-H43: Sin permiso schedules.delete → DELETE directo devuelve 403
 *
 * Run: vendor/bin/sail dusk tests/Browser/Academic/ScheduleDeleteTest.php
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

function adminForDelete(): User
{
    $user = User::factory()->create();
    $user->assignRole('Admin');

    return $user;
}

/**
 * Builds a deletable schedule with complete relationships.
 *
 * @return array{schedule: Schedule, subject: Subject}
 */
function buildDeletableSchedule(): array
{
    $career = Career::factory()->create(['name' => 'Carrera Delete Dusk']);
    $pensum = Pensum::factory()->create(['career_id' => $career->id]);
    $subject = Subject::factory()->create([
        'pensum_id' => $pensum->id,
        'name' => 'Materia Delete Dusk',
        'code' => 'DEL-001',
    ]);
    $period = Period::factory()->semester()->active()->create([
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);
    $section = Section::factory()->create([
        'period_id' => $period->id,
        'subject_id' => $subject->id,
        'type' => SectionType::University,
    ]);
    $professor = Professor::factory()->create(['weekly_hour_limit' => 40]);
    $classroom = Classroom::factory()->create(['identifier' => 'SALA-DEL']);

    $schedule = Schedule::factory()->create([
        'section_id' => $section->id,
        'professor_id' => $professor->id,
        'classroom_id' => $classroom->id,
        'subject_id' => $subject->id,
        'day_of_week' => DayOfWeek::Monday,
        'start_time' => '08:00:00',
        'end_time' => '08:45:00',
        'valid_from' => '2026-01-15',
    ]);

    return compact('schedule', 'subject');
}

// ---------------------------------------------------------------------------
// UC-H39 — Visibilidad de controles de eliminar
// ---------------------------------------------------------------------------

test('UC-H39a: admin ve controles de eliminar en la vista lista', function () {
    $user = adminForDelete();
    buildDeletableSchedule();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            ->click('[dusk="btn-view-list"]')
            ->waitFor('.sch-list', 5)
            ->assertPresent('[dusk="list-delete-btn"]');
    });
});

test('UC-H39b: usuario sin permiso schedules.delete no ve controles de eliminar', function () {
    $user = User::factory()->create();
    $user->assignRole('Profesor');
    $user->givePermissionTo('schedules.view');
    buildDeletableSchedule();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            ->click('[dusk="btn-view-list"]')
            ->waitFor('.sch-list', 5)
            ->assertMissing('[dusk="list-delete-btn"]');
    });
});

// ---------------------------------------------------------------------------
// UC-H40 — Modal de eliminación abre con datos del horario
// ---------------------------------------------------------------------------

test('UC-H40: clic en eliminar abre modal con datos del horario', function () {
    $user = adminForDelete();
    $fixture = buildDeletableSchedule();

    $this->browse(function (Browser $browser) use ($user, $fixture) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            ->click('[dusk="btn-view-list"]')
            ->waitFor('.sch-list', 5)
            ->click('[dusk="list-delete-btn"]')
            ->waitForText('Eliminar horario', 5)
            ->assertSee('Eliminar horario')
            ->assertSee($fixture['subject']->code);
    });
});

// ---------------------------------------------------------------------------
// UC-H41 — Confirmar eliminación
// ---------------------------------------------------------------------------

test('UC-H41: confirmar eliminación muestra toast y elimina el horario', function () {
    $user = adminForDelete();
    $fixture = buildDeletableSchedule();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            ->click('[dusk="btn-view-list"]')
            ->waitFor('.sch-list', 5)
            ->click('[dusk="list-delete-btn"]')
            ->waitForText('Eliminar horario', 5)
            ->click('[dusk="delete-confirm-btn"]')
            ->waitForText('Horario eliminado.', 8)
            ->assertSee('Horario eliminado.');
    });

    $this->assertDatabaseMissing('schedules', ['id' => $fixture['schedule']->id]);
});

// ---------------------------------------------------------------------------
// UC-H42 — Cancelar en modal de eliminación
// ---------------------------------------------------------------------------

test('UC-H42: cancelar en modal de eliminación no borra el horario', function () {
    $user = adminForDelete();
    $fixture = buildDeletableSchedule();

    $this->browse(function (Browser $browser) use ($user, $fixture) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            ->click('[dusk="btn-view-list"]')
            ->waitFor('.sch-list', 5)
            ->click('[dusk="list-delete-btn"]')
            ->waitForText('Eliminar horario', 5)
            ->click('[dusk="delete-cancel-btn"]')
            // Modal should close
            ->waitUntilMissing('[dusk="delete-cancel-btn"]', 4)
            // Schedule should still be in list
            ->assertSee($fixture['subject']->name);
    });

    $this->assertDatabaseHas('schedules', ['id' => $fixture['schedule']->id]);
});

// ---------------------------------------------------------------------------
// UC-H43 — Sin permiso schedules.delete → 403
// ---------------------------------------------------------------------------

test('UC-H43: usuario sin permiso schedules.delete recibe 403 en DELETE directo', function () {
    $fixture = buildDeletableSchedule();

    $user = User::factory()->create();
    $user->assignRole('Estudiante');

    // Use withoutMiddleware to bypass CSRF in this HTTP assertion (Dusk test context)
    $this->withoutMiddleware()
        ->actingAs($user)
        ->delete(route('scheduling.schedules.destroy', $fixture['schedule']))
        ->assertForbidden();
});
