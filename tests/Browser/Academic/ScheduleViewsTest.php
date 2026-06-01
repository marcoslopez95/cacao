<?php

/**
 * Browser (Dusk) tests — Horarios: Vistas, modals y permisos (H61–H73)
 *
 * UC cubiertos:
 * - UC-H65: Switcher de vista cambia entre WeeklyGrid y ScheduleListView sin round-trip
 * - UC-H66: Vista "Día" muestra solo un día a la vez con navegación por día
 * - UC-H67: Vista Lista muestra schedules con controles condicionados a permisos
 * - UC-H68: Clic en evento abre ScheduleClusterPopover con datos del horario
 * - UC-H69: Varios eventos en misma celda muestran tile de cluster; clic abre popover
 * - UC-H70: Clic en chip de conflictos en ScheduleStats abre popover de conflictos
 * - UC-H71: Usuario con schedules.view (sin create/update/delete) ve grilla pero no controles
 * - UC-H72: Usuario con todos los permisos tiene can.create/update/delete como true
 * - UC-H73: Usuario no autenticado redirige a /login
 *
 * Skipped (requieren implementación previa UC-H48):
 * - UC-H61, UC-H62, UC-H63, UC-H64
 *
 * Run: vendor/bin/sail dusk tests/Browser/Academic/ScheduleViewsTest.php
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

function adminForViews(): User
{
    $user = User::factory()->create();
    $user->assignRole('Admin');

    return $user;
}

/**
 * Creates one schedule ready for view tests.
 *
 * @return array{schedule: Schedule, subject: Subject}
 */
function buildViewableSchedule(string $subjectName = 'Materia Vista Dusk'): array
{
    $career = Career::factory()->create(['name' => "Carrera {$subjectName}"]);
    $pensum = Pensum::factory()->create(['career_id' => $career->id]);
    $subject = Subject::factory()->create([
        'pensum_id' => $pensum->id,
        'name' => $subjectName,
        'code' => 'VIS-001',
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
    $classroom = Classroom::factory()->create(['identifier' => 'SALA-VIS']);

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
// UC-H65 — Switcher de vista
// ---------------------------------------------------------------------------

test('UC-H65: switcher cambia de semana a lista sin round-trip', function () {
    $user = adminForViews();
    $fixture = buildViewableSchedule();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            // Default = week view (grid present)
            ->assertPresent('[dusk="weekly-grid"]')
            ->assertMissing('.sch-list')
            // Switch to list
            ->click('[dusk="btn-view-list"]')
            ->waitFor('.sch-list', 4)
            ->assertPresent('.sch-list')
            ->assertMissing('[dusk="weekly-grid"]')
            // Switch back to week
            ->click('[dusk="btn-view-week"]')
            ->waitFor('[dusk="weekly-grid"]', 4)
            ->assertPresent('[dusk="weekly-grid"]');

        // URL should not have changed (no round-trip)
        $currentUrl = $browser->driver->getCurrentURL();
        expect($currentUrl)->not->toContain('view=');
    });
});

// ---------------------------------------------------------------------------
// UC-H66 — Vista Día
// ---------------------------------------------------------------------------

test('UC-H66: vista Día muestra solo una columna de día con navegación', function () {
    $user = adminForViews();
    buildViewableSchedule();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            ->click('[dusk="btn-view-day"]')
            ->waitFor('[dusk="weekly-grid"]', 4)
            // In day mode the grid renders with a single day column visible
            ->assertPresent('[dusk="weekly-grid"]')
            // Only one day column should be visible (the others are hidden by CSS)
            ->assertPresent('.sch-day-col.active-mobile');
    });
});

// ---------------------------------------------------------------------------
// UC-H67 — Vista Lista
// ---------------------------------------------------------------------------

test('UC-H67: vista lista muestra todos los schedules con controles de editar/eliminar', function () {
    $user = adminForViews();
    $fixture = buildViewableSchedule('Materia Vista Lista');

    $this->browse(function (Browser $browser) use ($user, $fixture) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            ->click('[dusk="btn-view-list"]')
            ->waitFor('.sch-list', 4)
            ->assertSee($fixture['subject']->name)
            ->assertPresent('[dusk="list-edit-btn"]')
            ->assertPresent('[dusk="list-delete-btn"]');
    });
});

test('UC-H67b: vista lista sin permiso update/delete no muestra controles', function () {
    $user = User::factory()->create();
    $user->assignRole('Profesor');
    $user->givePermissionTo('schedules.view');
    buildViewableSchedule('Materia Lista Sin Permisos');

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            ->click('[dusk="btn-view-list"]')
            ->waitFor('.sch-list', 4)
            ->assertMissing('[dusk="list-edit-btn"]')
            ->assertMissing('[dusk="list-delete-btn"]');
    });
});

// ---------------------------------------------------------------------------
// UC-H68 — Clic en evento abre ScheduleClusterPopover
// ---------------------------------------------------------------------------

test('UC-H68: clic en evento de la grilla abre ScheduleClusterPopover con datos del horario', function () {
    $user = adminForViews();
    $fixture = buildViewableSchedule('Materia Popover Dusk');

    $this->browse(function (Browser $browser) use ($user, $fixture) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            ->waitFor('[dusk="weekly-grid"]', 5)
            // Click on the schedule event card
            ->click('[dusk="schedule-event-card"]')
            ->waitForText($fixture['subject']->name, 5)
            ->assertSee($fixture['subject']->name);
    });
});

// ---------------------------------------------------------------------------
// UC-H69 — Cluster tile
// ---------------------------------------------------------------------------

test('UC-H69: múltiples eventos solapados generan tile de cluster', function () {
    $user = adminForViews();

    // MAX_LANES = 3: need > 3 overlapping events to trigger the overflow tile.
    // Create 4 schedules in the same day/time slot with different professors & classrooms.
    $career = Career::factory()->create(['name' => 'Carrera Cluster A']);
    $pensum = Pensum::factory()->create(['career_id' => $career->id]);
    $period = Period::factory()->semester()->active()->create(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);

    for ($i = 1; $i <= 4; $i++) {
        $sub = Subject::factory()->create(['pensum_id' => $pensum->id, 'name' => "Cluster Sub {$i}", 'code' => "CLU-00{$i}"]);
        $sec = Section::factory()->create(['period_id' => $period->id, 'subject_id' => $sub->id]);
        $prof = Professor::factory()->create(['weekly_hour_limit' => 40]);
        $room = Classroom::factory()->create(['identifier' => "CLU-R{$i}"]);
        Schedule::factory()->create([
            'section_id' => $sec->id,
            'professor_id' => $prof->id,
            'classroom_id' => $room->id,
            'subject_id' => $sub->id,
            'day_of_week' => DayOfWeek::Wednesday,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'valid_from' => '2026-01-15',
        ]);
    }

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            ->waitFor('[dusk="weekly-grid"]', 5)
            // There should be a cluster tile (overflow tile)
            ->assertPresent('[dusk="schedule-overflow-tile"]');
    });
});

// ---------------------------------------------------------------------------
// UC-H70 — Chip de conflictos abre popover
// ---------------------------------------------------------------------------

test('UC-H70: clic en chip de conflictos en ScheduleStats abre popover', function () {
    $user = adminForViews();

    // Create two schedules with classroom conflict
    $career = Career::factory()->create(['name' => 'Carrera Conflict Stats']);
    $pensum = Pensum::factory()->create(['career_id' => $career->id]);
    $sub1 = Subject::factory()->create(['pensum_id' => $pensum->id, 'name' => 'Sub Stats 1', 'code' => 'CSS-001']);
    $sub2 = Subject::factory()->create(['pensum_id' => $pensum->id, 'name' => 'Sub Stats 2', 'code' => 'CSS-002']);
    $period = Period::factory()->semester()->active()->create(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
    $sec1 = Section::factory()->create(['period_id' => $period->id, 'subject_id' => $sub1->id]);
    $sec2 = Section::factory()->create(['period_id' => $period->id, 'subject_id' => $sub2->id]);
    $prof1 = Professor::factory()->create(['weekly_hour_limit' => 40]);
    $prof2 = Professor::factory()->create(['weekly_hour_limit' => 40]);
    $room = Classroom::factory()->create(['identifier' => 'STATS-ROOM']);

    Schedule::factory()->create([
        'section_id' => $sec1->id, 'professor_id' => $prof1->id, 'classroom_id' => $room->id,
        'subject_id' => $sub1->id, 'day_of_week' => DayOfWeek::Friday,
        'start_time' => '10:00:00', 'end_time' => '11:00:00', 'valid_from' => '2026-01-15',
    ]);
    Schedule::factory()->create([
        'section_id' => $sec2->id, 'professor_id' => $prof2->id, 'classroom_id' => $room->id,
        'subject_id' => $sub2->id, 'day_of_week' => DayOfWeek::Friday,
        'start_time' => '10:30:00', 'end_time' => '11:30:00', 'valid_from' => '2026-01-15',
    ]);

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            ->waitFor('.sch-stats', 5)
            // Click on the conflicts stat button
            ->click('[dusk="stats-conflicts-btn"]')
            ->waitForText('conflicto', 5)
            ->assertSee('conflicto');
    });
});

// ---------------------------------------------------------------------------
// UC-H71 — Usuario con solo schedules.view
// ---------------------------------------------------------------------------

test('UC-H71: usuario con schedules.view solo ve grilla, sin crear/editar/eliminar', function () {
    $user = User::factory()->create();
    $user->assignRole('Profesor');
    $user->givePermissionTo('schedules.view');
    buildViewableSchedule('Materia Solo Vista');

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            ->assertSee('Horarios')
            ->assertDontSee('+ Nuevo horario')
            ->click('[dusk="btn-view-list"]')
            ->waitFor('.sch-list', 4)
            ->assertMissing('[dusk="list-edit-btn"]')
            ->assertMissing('[dusk="list-delete-btn"]');
    });
});

// ---------------------------------------------------------------------------
// UC-H72 — Admin con todos los permisos
// ---------------------------------------------------------------------------

test('UC-H72: admin con todos los permisos ve todos los controles', function () {
    $user = adminForViews();
    buildViewableSchedule('Materia Permisos Full');

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            ->assertSee('+ Nuevo horario')
            ->click('[dusk="btn-view-list"]')
            ->waitFor('.sch-list', 4)
            ->assertPresent('[dusk="list-edit-btn"]')
            ->assertPresent('[dusk="list-delete-btn"]');
    });
});

// ---------------------------------------------------------------------------
// UC-H73 — Usuario no autenticado redirige a /login
// ---------------------------------------------------------------------------

test('UC-H73: usuario no autenticado que accede a /scheduling/schedules redirige a /login', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/scheduling/schedules')
            ->assertPathIs('/login');
    });
});
