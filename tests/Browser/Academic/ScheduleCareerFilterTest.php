<?php

/**
 * Browser (Dusk) tests — Horarios: Filtro por carrera (H74–H76)
 *
 * UC cubiertos:
 * - UC-H74: Toggle de carrera en leyenda dispara Inertia visit → schedules filtrados server-side
 * - UC-H75: CreateScheduleModal muestra banner + secciones filtradas cuando carrera activa
 * - UC-H76: career_ids persiste en URL al recargar la página
 *
 * Run: vendor/bin/sail dusk tests/Browser/Academic/ScheduleCareerFilterTest.php
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

function adminForCareerFilter(): User
{
    $user = User::factory()->create();
    $user->assignRole('Admin');

    return $user;
}

/**
 * Builds two careers each with one schedule, so the legend shows both pills.
 *
 * @return array{careerA: Career, careerB: Career, scheduleA: Schedule, scheduleB: Schedule}
 */
function buildTwoCareers(): array
{
    $period = Period::factory()->semester()->active()->create([
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);
    $professor = Professor::factory()->create(['weekly_hour_limit' => 40]);
    $classroom = Classroom::factory()->create(['identifier' => 'SALA-CF']);

    // Career A
    $careerA = Career::factory()->create(['name' => 'Ingeniería CF']);
    $pensumA = Pensum::factory()->create(['career_id' => $careerA->id]);
    $subjectA = Subject::factory()->create(['pensum_id' => $pensumA->id, 'code' => 'ING-CF1', 'name' => 'Algo Ingeniería']);
    $sectionA = Section::factory()->create([
        'period_id' => $period->id,
        'subject_id' => $subjectA->id,
        'type' => SectionType::University,
        'code' => 'A01',
    ]);
    $scheduleA = Schedule::factory()->create([
        'section_id' => $sectionA->id,
        'professor_id' => $professor->id,
        'classroom_id' => $classroom->id,
        'subject_id' => $subjectA->id,
        'day_of_week' => DayOfWeek::Monday,
        'start_time' => '08:00:00',
        'end_time' => '09:00:00',
        'type' => 'theory',
        'valid_from' => '2026-01-15',
    ]);

    // Career B
    $careerB = Career::factory()->create(['name' => 'Humanidades CF']);
    $pensumB = Pensum::factory()->create(['career_id' => $careerB->id]);
    $subjectB = Subject::factory()->create(['pensum_id' => $pensumB->id, 'code' => 'HUM-CF1', 'name' => 'Algo Humanidades']);
    $sectionB = Section::factory()->create([
        'period_id' => $period->id,
        'subject_id' => $subjectB->id,
        'type' => SectionType::University,
        'code' => 'B01',
    ]);
    $scheduleB = Schedule::factory()->create([
        'section_id' => $sectionB->id,
        'professor_id' => $professor->id,
        'classroom_id' => $classroom->id,
        'subject_id' => $subjectB->id,
        'day_of_week' => DayOfWeek::Tuesday,
        'start_time' => '10:00:00',
        'end_time' => '11:00:00',
        'type' => 'theory',
        'valid_from' => '2026-01-15',
    ]);

    return compact('careerA', 'careerB', 'scheduleA', 'scheduleB');
}

// ---------------------------------------------------------------------------
// UC-H74 — Toggle carrera filtra server-side
// ---------------------------------------------------------------------------

test('UC-H74: toggle de carrera en leyenda actualiza URL y filtra horarios server-side', function () {
    $user = adminForCareerFilter();
    $fixture = buildTwoCareers();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            // Both career pills must be visible in the legend
            ->waitForText('Ingeniería CF', 8)
            ->assertSee('Humanidades CF')
            // Click "Ingeniería CF" pill to filter — hides it from results
            ->click('[dusk="legend-career-btn"]')
            ->waitUntil("window.location.href.includes('career_ids')", 8);

        expect($browser->driver->getCurrentURL())->toContain('career_ids');
    });
});

// ---------------------------------------------------------------------------
// UC-H75 — Banner + secciones filtradas en CreateScheduleModal
// ---------------------------------------------------------------------------

test('UC-H75: CreateScheduleModal muestra banner cuando carrera activa y filtra secciones', function () {
    $user = adminForCareerFilter();
    $fixture = buildTwoCareers();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            ->waitForText('Ingeniería CF', 8)
            // Toggle career to activate career filter
            ->click('[dusk="legend-career-btn"]')
            ->waitUntil("window.location.href.includes('career_ids')", 8)
            ->waitForText('Horarios', 5);

        // URL has career_ids — open create modal
        $browser->click('[dusk="btn-create-schedule"]')
            ->waitForText('Nuevo horario', 5)
            ->assertPresent('[dusk="career-filter-banner"]')
            ->assertSee('Las secciones se muestran filtradas');
    });
});

// ---------------------------------------------------------------------------
// UC-H76 — career_ids persiste en URL al recargar
// ---------------------------------------------------------------------------

test('UC-H76: career_ids persiste en URL al recargar la página', function () {
    $user = adminForCareerFilter();
    $fixture = buildTwoCareers();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            ->waitForText('Ingeniería CF', 8)
            // Toggle career
            ->click('[dusk="legend-career-btn"]')
            ->waitUntil("window.location.href.includes('career_ids')", 8);

        $urlWithFilter = $browser->driver->getCurrentURL();
        expect($urlWithFilter)->toContain('career_ids');

        // Reload the same URL — filter must be restored
        $browser->visit($urlWithFilter)
            ->waitForText('Horarios', 10)
            ->waitForText('Ingeniería CF', 8);

        $urlAfterReload = $browser->driver->getCurrentURL();
        expect($urlAfterReload)->toContain('career_ids');
    });
});
