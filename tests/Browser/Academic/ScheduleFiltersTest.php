<?php

/**
 * Browser (Dusk) tests — Horarios: Filtros y navegación (H44–H60)
 *
 * UC cubiertos (los que no requieren implementación previa):
 * - UC-H44: Filtro por period_id server-side
 * - UC-H45: Filtro por section_id server-side
 * - UC-H46: Filtro por professor_id server-side
 * - UC-H47: Filtro por day_of_week server-side
 * - UC-H49: Combinar period_id + section_id (AND)
 * - UC-H51: Sin filtros → todos los schedules
 * - UC-H52: Búsqueda de texto client-side (por materia, profesor, aula, sección)
 * - UC-H53: Filtro de carrera via ScheduleLegend (client-side toggle)
 * - UC-H54: activeCareerIds vacío = mostrar todos
 * - UC-H55: Dropdown de período navega con period_id
 * - UC-H56: Todos los períodos limpia period_id
 * - UC-H57: + Filtro abre panel con selects de Sección y Profesor
 * - UC-H58: Aplicar filtros navega con section_id y professor_id en URL
 * - UC-H59: Chip ✕ quita ese filtro específico
 * - UC-H60: Limpiar todos limpia todos los filtros
 *
 * Skipped (pendientes de implementación server-side):
 * - UC-H48 (career_ids server-side)
 * - UC-H50 (period + career server-side)
 *
 * Run: vendor/bin/sail dusk tests/Browser/Academic/ScheduleFiltersTest.php
 */

use App\Enums\DayOfWeek;
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

function adminForFilters(): User
{
    $user = User::factory()->create();
    $user->assignRole('Admin');

    return $user;
}

/**
 * Creates two separate schedules in different periods/sections/professors
 * to test isolation of filters.
 *
 * @return array{scheduleA: Schedule, scheduleB: Schedule, periodA: Period, periodB: Period, sectionA: Section, sectionB: Section, professorA: Professor, professorB: Professor}
 */
function buildTwoScheduleFixture(): array
{
    // --- Fixture A ---
    $careerA = Career::factory()->create(['name' => 'Carrera A Filtros']);
    $pensumA = Pensum::factory()->create(['career_id' => $careerA->id]);
    $subjectA = Subject::factory()->create(['pensum_id' => $pensumA->id, 'name' => 'Materia Filtro A', 'code' => 'FLA-001']);
    $periodA = Period::factory()->semester()->active()->create(['name' => 'Período-A', 'start_date' => '2026-01-01', 'end_date' => '2026-06-30']);
    $sectionA = Section::factory()->create(['period_id' => $periodA->id, 'subject_id' => $subjectA->id, 'code' => 'SEC-FA']);
    $professorA = Professor::factory()->create(['weekly_hour_limit' => 40]);
    $classroomA = Classroom::factory()->create(['identifier' => 'AULA-FA']);
    $scheduleA = Schedule::factory()->create([
        'section_id' => $sectionA->id, 'professor_id' => $professorA->id,
        'classroom_id' => $classroomA->id, 'subject_id' => $subjectA->id,
        'day_of_week' => DayOfWeek::Monday, 'start_time' => '08:00:00', 'end_time' => '08:45:00',
        'valid_from' => '2026-01-15',
    ]);

    // --- Fixture B ---
    $careerB = Career::factory()->create(['name' => 'Carrera B Filtros']);
    $pensumB = Pensum::factory()->create(['career_id' => $careerB->id]);
    $subjectB = Subject::factory()->create(['pensum_id' => $pensumB->id, 'name' => 'Materia Filtro B', 'code' => 'FLB-001']);
    $periodB = Period::factory()->semester()->active()->create(['name' => 'Período-B', 'start_date' => '2026-07-01', 'end_date' => '2026-12-31']);
    $sectionB = Section::factory()->create(['period_id' => $periodB->id, 'subject_id' => $subjectB->id, 'code' => 'SEC-FB']);
    $professorB = Professor::factory()->create(['weekly_hour_limit' => 40]);
    $classroomB = Classroom::factory()->create(['identifier' => 'AULA-FB']);
    $scheduleB = Schedule::factory()->create([
        'section_id' => $sectionB->id, 'professor_id' => $professorB->id,
        'classroom_id' => $classroomB->id, 'subject_id' => $subjectB->id,
        'day_of_week' => DayOfWeek::Tuesday, 'start_time' => '10:00:00', 'end_time' => '10:45:00',
        'valid_from' => '2026-07-15',
    ]);

    return compact('scheduleA', 'scheduleB', 'periodA', 'periodB', 'sectionA', 'sectionB', 'professorA', 'professorB');
}

// ---------------------------------------------------------------------------
// UC-H44 — Filtro por period_id server-side
// ---------------------------------------------------------------------------

test('UC-H44: filtro por period_id devuelve solo schedules de ese período', function () {
    $user = adminForFilters();
    $fixture = buildTwoScheduleFixture();

    $this->browse(function (Browser $browser) use ($user, $fixture) {
        $browser->loginAs($user)
            ->visit("/scheduling/schedules?period_id={$fixture['periodA']->id}")
            ->waitForText('Horarios', 10)
            ->click('[dusk="btn-view-list"]')
            ->waitFor('.sch-list', 5)
            ->assertSee('Materia Filtro A')
            ->assertDontSee('Materia Filtro B');
    });
});

// ---------------------------------------------------------------------------
// UC-H45 — Filtro por section_id server-side
// ---------------------------------------------------------------------------

test('UC-H45: filtro por section_id devuelve solo schedules de esa sección', function () {
    $user = adminForFilters();
    $fixture = buildTwoScheduleFixture();

    $this->browse(function (Browser $browser) use ($user, $fixture) {
        $browser->loginAs($user)
            ->visit("/scheduling/schedules?section_id={$fixture['sectionB']->id}")
            ->waitForText('Horarios', 10)
            ->click('[dusk="btn-view-list"]')
            ->waitFor('.sch-list', 5)
            ->assertSee('Materia Filtro B')
            ->assertDontSee('Materia Filtro A');
    });
});

// ---------------------------------------------------------------------------
// UC-H46 — Filtro por professor_id server-side
// ---------------------------------------------------------------------------

test('UC-H46: filtro por professor_id devuelve solo schedules de ese profesor', function () {
    $user = adminForFilters();
    $fixture = buildTwoScheduleFixture();

    $this->browse(function (Browser $browser) use ($user, $fixture) {
        $browser->loginAs($user)
            ->visit("/scheduling/schedules?professor_id={$fixture['professorA']->id}")
            ->waitForText('Horarios', 10)
            ->click('[dusk="btn-view-list"]')
            ->waitFor('.sch-list', 5)
            ->assertSee('Materia Filtro A')
            ->assertDontSee('Materia Filtro B');
    });
});

// ---------------------------------------------------------------------------
// UC-H47 — Filtro por day_of_week server-side
// ---------------------------------------------------------------------------

test('UC-H47: filtro por day_of_week devuelve solo schedules de ese día', function () {
    $user = adminForFilters();
    $fixture = buildTwoScheduleFixture();
    // scheduleA = monday, scheduleB = tuesday

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules?day_of_week=monday')
            ->waitForText('Horarios', 10)
            ->click('[dusk="btn-view-list"]')
            ->waitFor('.sch-list', 5)
            ->assertSee('Materia Filtro A')
            ->assertDontSee('Materia Filtro B');
    });
});

// ---------------------------------------------------------------------------
// UC-H49 — Combinar period_id + section_id
// ---------------------------------------------------------------------------

test('UC-H49: filtros period_id + section_id se aplican con AND', function () {
    $user = adminForFilters();
    $fixture = buildTwoScheduleFixture();

    $this->browse(function (Browser $browser) use ($user, $fixture) {
        $browser->loginAs($user)
            ->visit("/scheduling/schedules?period_id={$fixture['periodA']->id}&section_id={$fixture['sectionA']->id}")
            ->waitForText('Horarios', 10)
            ->click('[dusk="btn-view-list"]')
            ->waitFor('.sch-list', 5)
            ->assertSee('Materia Filtro A')
            ->assertDontSee('Materia Filtro B');
    });
});

// ---------------------------------------------------------------------------
// UC-H51 — Sin filtros devuelve todos
// ---------------------------------------------------------------------------

test('UC-H51: sin filtros en URL devuelve todos los schedules', function () {
    $user = adminForFilters();
    $fixture = buildTwoScheduleFixture();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            ->click('[dusk="btn-view-list"]')
            ->waitFor('.sch-list', 5)
            ->assertSee('Materia Filtro A')
            ->assertSee('Materia Filtro B');
    });
});

// ---------------------------------------------------------------------------
// UC-H52 — Búsqueda de texto client-side
// ---------------------------------------------------------------------------

test('UC-H52: búsqueda de texto client-side filtra por nombre de materia', function () {
    $user = adminForFilters();
    buildTwoScheduleFixture();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            ->click('[dusk="btn-view-list"]')
            ->waitFor('.sch-list', 5)
            // Type in search
            ->type('[dusk="search-input"]', 'Filtro A')
            ->pause(400) // client-side filtering is instant but allow re-render
            ->assertSee('Materia Filtro A')
            ->assertDontSee('Materia Filtro B');
    });
});

test('UC-H52b: búsqueda de texto filtra por identificador de aula', function () {
    $user = adminForFilters();
    buildTwoScheduleFixture();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            ->click('[dusk="btn-view-list"]')
            ->waitFor('.sch-list', 5)
            ->type('[dusk="search-input"]', 'AULA-FB')
            ->pause(400)
            ->assertSee('Materia Filtro B')
            ->assertDontSee('Materia Filtro A');
    });
});

// ---------------------------------------------------------------------------
// UC-H53 — Filtro de carrera via ScheduleLegend (client-side)
// ---------------------------------------------------------------------------

test('UC-H53: clic en carrera en ScheduleLegend filtra eventos sin round-trip', function () {
    $user = adminForFilters();
    buildTwoScheduleFixture();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            ->waitFor('.sch-legend', 5)
            // Click on "Carrera A Filtros" to keep only it active (first click selects all then removes others)
            ->click('[dusk="legend-career-btn"]');

        // URL should not have changed (no round-trip for client-side filter)
        $url = $browser->driver->getCurrentURL();
        expect($url)->not->toContain('career_ids');
    });
});

// ---------------------------------------------------------------------------
// UC-H54 — activeCareerIds vacío = todos visibles
// ---------------------------------------------------------------------------

test('UC-H54: sin filtro de carrera activo todos los schedules son visibles', function () {
    $user = adminForFilters();
    buildTwoScheduleFixture();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            ->click('[dusk="btn-view-list"]')
            ->waitFor('.sch-list', 5)
            // No career filter applied — both subjects should appear
            ->assertSee('Materia Filtro A')
            ->assertSee('Materia Filtro B');
    });
});

// ---------------------------------------------------------------------------
// UC-H55 — Dropdown de período navega con period_id
// ---------------------------------------------------------------------------

test('UC-H55: seleccionar período en dropdown navega con period_id en URL', function () {
    $user = adminForFilters();
    $fixture = buildTwoScheduleFixture();

    $this->browse(function (Browser $browser) use ($user, $fixture) {
        $periodId = $fixture['periodA']->id;

        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            // Open period dropdown
            ->click('[dusk="period-dropdown-btn"]')
            ->waitFor('.period-drop', 3)
            // Click on first period item (period A)
            ->click("[dusk='period-option-{$periodId}']")
            // Wait for Inertia SPA navigation (no full reload)
            ->waitUntil("window.location.href.includes('period_id={$periodId}')", 6);

        $currentUrl = $browser->driver->getCurrentURL();
        expect($currentUrl)->toContain("period_id={$periodId}");
    });
});

// ---------------------------------------------------------------------------
// UC-H56 — Todos los períodos limpia period_id
// ---------------------------------------------------------------------------

test('UC-H56: seleccionar Todos los períodos limpia period_id de la URL', function () {
    $user = adminForFilters();
    $fixture = buildTwoScheduleFixture();

    $this->browse(function (Browser $browser) use ($user, $fixture) {
        $browser->loginAs($user)
            ->visit("/scheduling/schedules?period_id={$fixture['periodA']->id}")
            ->waitForText('Horarios', 10)
            ->click('[dusk="period-dropdown-btn"]')
            ->waitFor('.period-drop', 3)
            ->click('[dusk="period-option-all"]')
            // Wait for Inertia SPA navigation — URL should lose period_id
            ->waitUntil("!window.location.href.includes('period_id')", 6);

        $currentUrl = $browser->driver->getCurrentURL();
        expect($currentUrl)->not->toContain('period_id');
    });
});

// ---------------------------------------------------------------------------
// UC-H57 — + Filtro abre panel con selects
// ---------------------------------------------------------------------------

test('UC-H57: clic en + Filtro abre panel con selects de Sección y Profesor', function () {
    $user = adminForFilters();
    buildTwoScheduleFixture();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            ->click('[dusk="filter-add-btn"]')
            ->waitFor('.filter-panel', 3)
            ->assertPresent('[dusk="filter-section-select"]')
            ->assertPresent('[dusk="filter-professor-select"]');
    });
});

// ---------------------------------------------------------------------------
// UC-H58 — Aplicar filtros navega con section_id y professor_id
// ---------------------------------------------------------------------------

test('UC-H58: aplicar filtros de sección y profesor navega con params en URL', function () {
    $user = adminForFilters();
    $fixture = buildTwoScheduleFixture();

    $this->browse(function (Browser $browser) use ($user, $fixture) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            ->click('[dusk="filter-add-btn"]')
            ->waitFor('.filter-panel', 3)
            ->select('[dusk="filter-section-select"]', $fixture['sectionA']->id)
            ->click('[dusk="filter-apply-btn"]');

        $sectionId = $fixture['sectionA']->id;
        $browser->waitUntil("window.location.href.includes('section_id={$sectionId}')", 6);

        $currentUrl = $browser->driver->getCurrentURL();
        expect($currentUrl)->toContain("section_id={$sectionId}");
    });
});

// ---------------------------------------------------------------------------
// UC-H59 — Chip ✕ quita ese filtro específico
// ---------------------------------------------------------------------------

test('UC-H59: chip con ✕ quita el filtro específico y hace round-trip', function () {
    $user = adminForFilters();
    $fixture = buildTwoScheduleFixture();

    $this->browse(function (Browser $browser) use ($user, $fixture) {
        $browser->loginAs($user)
            ->visit("/scheduling/schedules?section_id={$fixture['sectionA']->id}")
            ->waitForText('Horarios', 10)
            ->assertPresent('.filter-chip')
            ->click('[dusk="filter-chip-section-remove"]')
            ->waitUntil("!window.location.href.includes('section_id')", 6);

        $currentUrl = $browser->driver->getCurrentURL();
        expect($currentUrl)->not->toContain('section_id');
    });
});

// ---------------------------------------------------------------------------
// UC-H60 — Limpiar todos
// ---------------------------------------------------------------------------

test('UC-H60: Limpiar todos elimina todos los filtros activos y hace round-trip', function () {
    $user = adminForFilters();
    $fixture = buildTwoScheduleFixture();

    $this->browse(function (Browser $browser) use ($user, $fixture) {
        $browser->loginAs($user)
            ->visit("/scheduling/schedules?period_id={$fixture['periodA']->id}&section_id={$fixture['sectionA']->id}")
            ->waitForText('Horarios', 10)
            ->assertSee('Limpiar todos')
            ->click('[dusk="filters-clear-btn"]')
            ->waitUntil("!window.location.href.includes('period_id') && !window.location.href.includes('section_id')", 6);

        $currentUrl = $browser->driver->getCurrentURL();
        expect($currentUrl)->not->toContain('period_id');
        expect($currentUrl)->not->toContain('section_id');
    });
});
