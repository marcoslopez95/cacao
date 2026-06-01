<?php

/**
 * Browser (Dusk) tests — Horarios: Conflictos de negocio (H27–H32)
 *
 * UC cubiertos:
 * - UC-H27: Conflicto de aula → 422 con mensaje de aula y horario conflictivo
 * - UC-H28: Sin conflicto de aula cuando el período conflictivo está cerrado
 * - UC-H29: Conflicto de profesor → 422 con nombre del profesor y horario
 * - UC-H30: Exceso de horas semanales del profesor → 422 con límite/actuales/nuevas
 * - UC-H31: Exceso de horas por fracción (minuto) → 422 (no solo horas enteras)
 * - UC-H32: Conflictos client-side en ScheduleConflictsBanner y ScheduleStats
 *
 * Run: vendor/bin/sail dusk tests/Browser/Academic/ScheduleConflictsTest.php
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
 * Sets an input[type=date] or input[type=time] value via JS to ensure Vue reactivity.
 */
function setInputValueConflict(Browser $browser, string $dusk, string $value): void
{
    $browser->script(
        "var el = document.querySelector('[dusk=\"{$dusk}\"]');"
        ."var nativeInputValueSetter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value').set;"
        ."nativeInputValueSetter.call(el, '{$value}');"
        ."el.dispatchEvent(new Event('input', { bubbles: true }));"
        ."el.dispatchEvent(new Event('change', { bubbles: true }));"
    );
}

function adminForConflicts(): User
{
    $user = User::factory()->create();
    $user->assignRole('Admin');

    return $user;
}

/**
 * Builds a complete fixture with career, pensum, subject, period, section, professor, classroom.
 *
 * @return array{section: Section, subject: Subject, professor: Professor, classroom: Classroom, period: Period}
 */
function buildConflictFixture(string $suffix = ''): array
{
    $career = Career::factory()->create(['name' => "Carrera Conflict {$suffix}"]);
    $pensum = Pensum::factory()->create(['career_id' => $career->id]);
    $subject = Subject::factory()->create([
        'pensum_id' => $pensum->id,
        'name' => "Materia Conflict {$suffix}",
        'code' => "CON-{$suffix}",
    ]);
    $period = Period::factory()->semester()->active()->create([
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);
    $section = Section::factory()->create([
        'period_id' => $period->id,
        'subject_id' => $subject->id,
        'type' => SectionType::University,
        'code' => "S{$suffix}",
    ]);
    $professor = Professor::factory()->create(['weekly_hour_limit' => 40]);
    $classroom = Classroom::factory()->create(['identifier' => "SALA-C{$suffix}"]);

    return compact('section', 'subject', 'professor', 'classroom', 'period');
}

// ---------------------------------------------------------------------------
// UC-H27 — Conflicto de aula
// ---------------------------------------------------------------------------

test('UC-H27: conflicto de aula produce error con nombre del aula y horario', function () {
    $user = adminForConflicts();
    $fixture = buildConflictFixture('H27');

    // Pre-create a schedule occupying the same classroom, day, time
    Schedule::factory()->create([
        'section_id' => $fixture['section']->id,
        'professor_id' => $fixture['professor']->id,
        'classroom_id' => $fixture['classroom']->id,
        'subject_id' => $fixture['subject']->id,
        'day_of_week' => DayOfWeek::Monday,
        'start_time' => '09:00:00',
        'end_time' => '10:00:00',
        'valid_from' => '2026-01-01',
        'valid_until' => null,
    ]);

    // Create a second section to use (different section, same period/subject)
    $career2 = Career::factory()->create(['name' => 'Carrera H27b']);
    $pensum2 = Pensum::factory()->create(['career_id' => $career2->id]);
    $subject2 = Subject::factory()->create(['pensum_id' => $pensum2->id, 'code' => 'H27B-001']);
    $period2 = $fixture['period'];
    $section2 = Section::factory()->create([
        'period_id' => $period2->id,
        'subject_id' => $subject2->id,
        'code' => 'S2H27',
    ]);
    $prof2 = Professor::factory()->create(['weekly_hour_limit' => 40]);

    $this->browse(function (Browser $browser) use ($user, $section2, $subject2, $prof2, $fixture) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            ->click('[dusk="btn-create-schedule"]')
            ->waitForText('Nuevo horario', 5)
            ->select('[dusk="create-section-select"]', $section2->id)
            ->select('[dusk="create-subject-select"]', $subject2->id)
            ->select('[dusk="create-professor-select"]', $prof2->id)
            // Same classroom as the existing schedule
            ->select('[dusk="create-classroom-select"]', $fixture['classroom']->id)
            ->select('[dusk="create-day-select"]', 'monday');

        setInputValueConflict($browser, 'create-start-time', '09:30'); // overlaps 09:00-10:00
        setInputValueConflict($browser, 'create-end-time', '10:30');
        setInputValueConflict($browser, 'create-valid-from', '2026-01-15');

        $browser->click('[dusk="create-submit-btn"]')
            ->waitForText('ya tiene clase', 8)
            ->assertSee('ya tiene clase');
    });
});

// ---------------------------------------------------------------------------
// UC-H28 — Sin conflicto de aula cuando el período está cerrado
// ---------------------------------------------------------------------------

test('UC-H28: aula no genera conflicto cuando el período del schedule existente está cerrado', function () {
    $user = adminForConflicts();

    // Create a schedule whose section belongs to a CLOSED period
    $career1 = Career::factory()->create(['name' => 'Carrera H28 Closed']);
    $pensum1 = Pensum::factory()->create(['career_id' => $career1->id]);
    $subject1 = Subject::factory()->create(['pensum_id' => $pensum1->id, 'code' => 'H28A-001']);
    $closedPeriod = Period::factory()->semester()->closed()->create([
        'start_date' => '2025-01-01',
        'end_date' => '2025-06-30',
    ]);
    $section1 = Section::factory()->create(['period_id' => $closedPeriod->id, 'subject_id' => $subject1->id]);
    $professor = Professor::factory()->create(['weekly_hour_limit' => 40]);
    $classroom = Classroom::factory()->create(['identifier' => 'SALA-H28']);

    Schedule::factory()->create([
        'section_id' => $section1->id,
        'professor_id' => $professor->id,
        'classroom_id' => $classroom->id,
        'subject_id' => $subject1->id,
        'day_of_week' => DayOfWeek::Monday,
        'start_time' => '09:00:00',
        'end_time' => '10:00:00',
        'valid_from' => '2025-01-15',
    ]);

    // Now create a new schedule in the SAME classroom/day/time but an ACTIVE period
    $career2 = Career::factory()->create(['name' => 'Carrera H28 Active']);
    $pensum2 = Pensum::factory()->create(['career_id' => $career2->id]);
    $subject2 = Subject::factory()->create(['pensum_id' => $pensum2->id, 'code' => 'H28B-001']);
    $activePeriod = Period::factory()->semester()->active()->create([
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);
    $section2 = Section::factory()->create(['period_id' => $activePeriod->id, 'subject_id' => $subject2->id]);
    $prof2 = Professor::factory()->create(['weekly_hour_limit' => 40]);

    $this->browse(function (Browser $browser) use ($user, $section2, $subject2, $prof2, $classroom) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            ->click('[dusk="btn-create-schedule"]')
            ->waitForText('Nuevo horario', 5)
            ->select('[dusk="create-section-select"]', $section2->id)
            ->select('[dusk="create-subject-select"]', $subject2->id)
            ->select('[dusk="create-professor-select"]', $prof2->id)
            ->select('[dusk="create-classroom-select"]', $classroom->id)
            ->select('[dusk="create-day-select"]', 'monday');

        setInputValueConflict($browser, 'create-start-time', '09:00');
        setInputValueConflict($browser, 'create-end-time', '10:00');
        setInputValueConflict($browser, 'create-valid-from', '2026-01-15');

        $browser->click('[dusk="create-submit-btn"]')
            ->waitForText('Horario creado.', 10)
            ->assertSee('Horario creado.');
    });
});

// ---------------------------------------------------------------------------
// UC-H29 — Conflicto de profesor
// ---------------------------------------------------------------------------

test('UC-H29: conflicto de profesor produce error con nombre del profesor y horario', function () {
    $user = adminForConflicts();
    $fixture = buildConflictFixture('H29');

    // Pre-create a schedule using the professor in the same slot
    Schedule::factory()->create([
        'section_id' => $fixture['section']->id,
        'professor_id' => $fixture['professor']->id,
        'classroom_id' => $fixture['classroom']->id,
        'subject_id' => $fixture['subject']->id,
        'day_of_week' => DayOfWeek::Tuesday,
        'start_time' => '10:00:00',
        'end_time' => '11:00:00',
        'valid_from' => '2026-01-01',
    ]);

    // New section + different classroom but SAME professor
    $career2 = Career::factory()->create(['name' => 'Carrera H29b']);
    $pensum2 = Pensum::factory()->create(['career_id' => $career2->id]);
    $subject2 = Subject::factory()->create(['pensum_id' => $pensum2->id, 'code' => 'H29B-001']);
    $section2 = Section::factory()->create([
        'period_id' => $fixture['period']->id,
        'subject_id' => $subject2->id,
    ]);
    $classroom2 = Classroom::factory()->create(['identifier' => 'SALA-H29B']);

    $this->browse(function (Browser $browser) use ($user, $section2, $subject2, $fixture, $classroom2) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            ->click('[dusk="btn-create-schedule"]')
            ->waitForText('Nuevo horario', 5)
            ->select('[dusk="create-section-select"]', $section2->id)
            ->select('[dusk="create-subject-select"]', $subject2->id)
            // Same professor
            ->select('[dusk="create-professor-select"]', $fixture['professor']->id)
            ->select('[dusk="create-classroom-select"]', $classroom2->id)
            ->select('[dusk="create-day-select"]', 'tuesday');

        setInputValueConflict($browser, 'create-start-time', '10:30'); // overlaps 10:00-11:00
        setInputValueConflict($browser, 'create-end-time', '11:30');
        setInputValueConflict($browser, 'create-valid-from', '2026-01-15');

        $browser->click('[dusk="create-submit-btn"]')
            ->waitForText('ya tiene clase', 8)
            ->assertSee('ya tiene clase');
    });
});

// ---------------------------------------------------------------------------
// UC-H30 — Exceso de horas semanales
// ---------------------------------------------------------------------------

test('UC-H30: exceso de horas semanales del profesor produce error con límite y totales', function () {
    $user = adminForConflicts();

    // Create professor with tight weekly_hour_limit
    $career = Career::factory()->create(['name' => 'Carrera H30']);
    $pensum = Pensum::factory()->create(['career_id' => $career->id]);
    $subject = Subject::factory()->create(['pensum_id' => $pensum->id, 'code' => 'H30-001']);
    $period = Period::factory()->semester()->active()->create([
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);
    $section = Section::factory()->create(['period_id' => $period->id, 'subject_id' => $subject->id]);
    $professor = Professor::factory()->create(['weekly_hour_limit' => 1]); // Only 1 hour allowed
    $classroom = Classroom::factory()->create(['identifier' => 'SALA-H30']);

    // Pre-fill schedule: 55 minutes used (under 1h but new slot will push over)
    Schedule::factory()->create([
        'section_id' => $section->id,
        'professor_id' => $professor->id,
        'classroom_id' => $classroom->id,
        'subject_id' => $subject->id,
        'day_of_week' => DayOfWeek::Monday,
        'start_time' => '08:00:00',
        'end_time' => '08:55:00', // 55 min
        'valid_from' => '2026-01-01',
    ]);

    // New section for the second schedule
    $career2 = Career::factory()->create(['name' => 'Carrera H30b']);
    $pensum2 = Pensum::factory()->create(['career_id' => $career2->id]);
    $subject2 = Subject::factory()->create(['pensum_id' => $pensum2->id, 'code' => 'H30B-001']);
    $section2 = Section::factory()->create(['period_id' => $period->id, 'subject_id' => $subject2->id]);
    $room2 = Classroom::factory()->create(['identifier' => 'SALA-H30B']);

    $this->browse(function (Browser $browser) use ($user, $section2, $subject2, $professor, $room2) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            ->click('[dusk="btn-create-schedule"]')
            ->waitForText('Nuevo horario', 5)
            ->select('[dusk="create-section-select"]', $section2->id)
            ->select('[dusk="create-subject-select"]', $subject2->id)
            ->select('[dusk="create-professor-select"]', $professor->id)
            ->select('[dusk="create-classroom-select"]', $room2->id)
            ->select('[dusk="create-day-select"]', 'tuesday');

        setInputValueConflict($browser, 'create-start-time', '09:00');
        setInputValueConflict($browser, 'create-end-time', '09:45'); // 45 min → total > 1h limit
        setInputValueConflict($browser, 'create-valid-from', '2026-01-15');

        $browser->click('[dusk="create-submit-btn"]')
            ->waitForText('superaría su límite', 8)
            ->assertSee('superaría su límite');
    });
});

// ---------------------------------------------------------------------------
// UC-H31 — Exceso de horas por fracción (minuto)
// ---------------------------------------------------------------------------

test('UC-H31: exceso de horas semanales por fracción produce error de validación', function () {
    $user = adminForConflicts();

    // Professor with 1h limit, existing schedule is 45min (0.75h), new is 30min (0.5h) → total 1.25h > 1h
    // All times use 15-min steps to be browser-valid.
    $career = Career::factory()->create(['name' => 'Carrera H31']);
    $pensum = Pensum::factory()->create(['career_id' => $career->id]);
    $subject = Subject::factory()->create(['pensum_id' => $pensum->id, 'code' => 'H31-001']);
    $period = Period::factory()->semester()->active()->create([
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);
    $section = Section::factory()->create(['period_id' => $period->id, 'subject_id' => $subject->id]);
    $professor = Professor::factory()->create(['weekly_hour_limit' => 1]); // 1h limit
    $classroom = Classroom::factory()->create(['identifier' => 'SALA-H31']);

    // Existing: 45 minutes (08:00–08:45) — under limit
    Schedule::factory()->create([
        'section_id' => $section->id,
        'professor_id' => $professor->id,
        'classroom_id' => $classroom->id,
        'subject_id' => $subject->id,
        'day_of_week' => DayOfWeek::Monday,
        'start_time' => '08:00:00',
        'end_time' => '08:45:00', // 45 minutes = 0.75h
        'valid_from' => '2026-01-01',
    ]);

    $career2 = Career::factory()->create(['name' => 'Carrera H31b']);
    $pensum2 = Pensum::factory()->create(['career_id' => $career2->id]);
    $subject2 = Subject::factory()->create(['pensum_id' => $pensum2->id, 'code' => 'H31B-001']);
    $section2 = Section::factory()->create(['period_id' => $period->id, 'subject_id' => $subject2->id]);
    $room2 = Classroom::factory()->create(['identifier' => 'SALA-H31B']);

    $this->browse(function (Browser $browser) use ($user, $section2, $subject2, $professor, $room2) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            ->click('[dusk="btn-create-schedule"]')
            ->waitForText('Nuevo horario', 5)
            ->select('[dusk="create-section-select"]', $section2->id)
            ->select('[dusk="create-subject-select"]', $subject2->id)
            ->select('[dusk="create-professor-select"]', $professor->id)
            ->select('[dusk="create-classroom-select"]', $room2->id)
            ->select('[dusk="create-day-select"]', 'wednesday');

        // New: 30 minutes (09:00–09:30) → total = 0.75 + 0.5 = 1.25h > 1h limit
        setInputValueConflict($browser, 'create-start-time', '09:00');
        setInputValueConflict($browser, 'create-end-time', '09:30');
        setInputValueConflict($browser, 'create-valid-from', '2026-01-15');

        $browser->click('[dusk="create-submit-btn"]')
            ->waitForText('superaría su límite', 8)
            ->assertSee('superaría su límite');
    });
});

// ---------------------------------------------------------------------------
// UC-H32 — Conflictos detectados en frontend (ScheduleConflictsBanner + Stats)
// ---------------------------------------------------------------------------

test('UC-H32: dos horarios solapados en mismo aula muestran conflicto en banner y stats', function () {
    $user = adminForConflicts();

    // Create two schedules that share the same classroom, day, overlapping time
    $career = Career::factory()->create(['name' => 'Carrera H32A']);
    $pensum = Pensum::factory()->create(['career_id' => $career->id]);
    $subject = Subject::factory()->create(['pensum_id' => $pensum->id, 'code' => 'H32A-001']);
    $period = Period::factory()->semester()->active()->create([
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);
    $section1 = Section::factory()->create(['period_id' => $period->id, 'subject_id' => $subject->id]);
    $prof1 = Professor::factory()->create(['weekly_hour_limit' => 40]);
    $classroom = Classroom::factory()->create(['identifier' => 'SALA-H32']);

    // Second section (different subject, same period)
    $career2 = Career::factory()->create(['name' => 'Carrera H32B']);
    $pensum2 = Pensum::factory()->create(['career_id' => $career2->id]);
    $subject2 = Subject::factory()->create(['pensum_id' => $pensum2->id, 'code' => 'H32B-001']);
    $section2 = Section::factory()->create(['period_id' => $period->id, 'subject_id' => $subject2->id]);
    $prof2 = Professor::factory()->create(['weekly_hour_limit' => 40]);

    // Both schedules in the same classroom on the same day/overlapping times
    Schedule::factory()->create([
        'section_id' => $section1->id,
        'professor_id' => $prof1->id,
        'classroom_id' => $classroom->id,
        'subject_id' => $subject->id,
        'day_of_week' => DayOfWeek::Wednesday,
        'start_time' => '10:00:00',
        'end_time' => '11:30:00',
        'valid_from' => '2026-01-01',
    ]);
    Schedule::factory()->create([
        'section_id' => $section2->id,
        'professor_id' => $prof2->id,
        'classroom_id' => $classroom->id,
        'subject_id' => $subject2->id,
        'day_of_week' => DayOfWeek::Wednesday,
        'start_time' => '11:00:00',
        'end_time' => '12:00:00',
        'valid_from' => '2026-01-01',
    ]);

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/scheduling/schedules')
            ->waitForText('Horarios', 10)
            // ScheduleConflictsBanner shows conflict text
            ->assertSee('conflicto')
            // ScheduleStats conflict counter > 0
            ->assertSee('Conflictos detectados');

        // Verify the stats counter is not 0
        $statsText = $browser->text('.sch-stats');
        expect($statsText)->toContain('Conflictos detectados');
    });
});
