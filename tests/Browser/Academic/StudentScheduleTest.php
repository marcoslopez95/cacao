<?php

/**
 * Browser (Dusk) tests — 21-student-schedule-view (Task 2: frontend read-only)
 *
 * Contrato de tests para `resources/js/pages/student/Schedule.vue` montando
 * `WeeklyGrid` (`hidden lg:block`) y `ScheduleListView` (`lg:hidden`), ambos
 * read-only (`can-update`/`can-delete` en `false`, sin wiring de click).
 *
 * Estos tests DEBEN estar en ROJO antes de Task 2 (el componente hoy es un stub
 * con solo un `<h1>`, sin montar `WeeklyGrid` ni `ScheduleListView`) y en VERDE
 * después. El implementer NO debe modificar este archivo.
 *
 * UCs cubiertos aquí (specs/21-student-schedule-view/qa.md):
 * - UC-QA-01 (mitad frontend) — grid semanal completo en desktop con datos correctos
 * - UC-QA-02 — viewport mobile muestra ScheduleListView en vez del grid
 * - UC-QA-03 (mitad frontend, agregado en auditoría Task 4/5) — estado vacío sin
 *   período activo (desktop) / sin enrollment confirmado (mobile), sin error visible
 * - UC-QA-06 — link "Ver horario completo →" del dashboard navega a /student/schedule
 *   (contrato de Task 3 — tester [pre]; ver bloque dedicado más abajo)
 * - UC-QA-07 (mitad frontend) — estudiante primary/secondary accede sin bloqueo visual
 * - UC-QA-08 — click sobre un evento no dispara navegación ni popover (desktop + mobile)
 *
 * NO cubiertos aquí (fuera de alcance de Task 2):
 * - UC-QA-04/05/09 — ya cubiertos en tests/Feature/StudentScheduleView/Acceptance (Task 1)
 *
 * Selectores/props asumidos de WeeklyGrid.vue / ScheduleListView.vue (NO modificados
 * por este archivo, léelos en resources/js/components/scheduling/):
 * - `[dusk="weekly-grid"]` — contenedor raíz de WeeklyGrid
 * - `[dusk="schedule-event-card"]` — cada evento renderizado dentro del grid (ScheduleEventCard)
 * - `.sch-list` — contenedor raíz de ScheduleListView (sin atributo dusk propio)
 * - `.list-row` — cada fila de evento dentro de ScheduleListView
 * - `[dusk="list-edit-btn"]` / `[dusk="list-delete-btn"]` — solo se renderizan si
 *   `can-update`/`can-delete` son `true`; en esta vista deben estar SIEMPRE ausentes
 * - Ninguno de los dos componentes emite navegación propia — el "sin efecto" al hacer
 *   click depende de que `student/Schedule.vue` NO wire los emits `openEvent` /
 *   `editSchedule` / `deleteSchedule` a ningún handler (ni popover, ni router)
 *
 * Selector asumido en `student/Dashboard.vue` para el contrato de Task 3 (NO existe
 * todavía — el implementer debe agregarlo al link "Ver horario completo →" dentro del
 * widget "Hoy"):
 * - `[dusk="view-full-schedule-link"]` — el `<Link>` de Wayfinder hacia
 *   `student.schedule.index`, dentro del bloque "Hoy" (`v-else` de `props.enrollment`)
 *
 * Run: vendor/bin/sail dusk tests/Browser/Academic/StudentScheduleTest.php
 */

use App\Enums\DayOfWeek;
use App\Enums\EnrollmentDetailStatus;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\EnrollmentDetail;
use App\Models\Period;
use App\Models\Professor;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Spatie\Permission\PermissionRegistrar;

uses(DatabaseMigrations::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Crea un estudiante con una sección confirmada y vigente en el período activo,
 * lista para verse reflejada en /student/schedule.
 *
 * @return array{
 *     student: Student,
 *     schedule: Schedule,
 *     subject: Subject,
 *     professor: Professor,
 *     classroom: Classroom,
 *     section: Section,
 * }
 */
function studentScheduleFixture(?Student $student = null): array
{
    $student ??= Student::factory()->create();

    $period = Period::factory()->active()->create();

    $enrollment = Enrollment::factory()->confirmed()->create([
        'student_id' => $student->id,
        'period_id' => $period->id,
    ]);

    $subject = Subject::factory()->create([
        'name' => 'Cálculo I',
        'code' => 'MAT-101',
    ]);
    $section = Section::factory()->create([
        'subject_id' => $subject->id,
        'period_id' => $period->id,
    ]);
    $professor = Professor::factory()->create();
    $classroom = Classroom::factory()->create(['identifier' => 'AULA-HOR-01']);

    $schedule = Schedule::factory()->create([
        'section_id' => $section->id,
        'subject_id' => $subject->id,
        'professor_id' => $professor->id,
        'classroom_id' => $classroom->id,
        'day_of_week' => DayOfWeek::Tuesday,
        'start_time' => '09:00:00',
        'end_time' => '09:45:00',
        'valid_from' => now()->subYear()->toDateString(),
        'valid_until' => null,
    ]);

    EnrollmentDetail::factory()->create([
        'enrollment_id' => $enrollment->id,
        'subject_id' => $subject->id,
        'section_id' => $section->id,
        'status' => EnrollmentDetailStatus::Confirmed,
    ]);

    return compact('student', 'schedule', 'subject', 'professor', 'classroom', 'section');
}

// ---------------------------------------------------------------------------
// UC-QA-01 (mitad frontend) — grid semanal completo en desktop
// ---------------------------------------------------------------------------

test('UC-QA-01: estudiante con clase confirmada y vigente ve el grid semanal completo en desktop', function () {
    $fixture = studentScheduleFixture();

    $this->browse(function (Browser $browser) use ($fixture) {
        $browser->resize(1440, 900)
            ->loginAs($fixture['student']->user)
            ->visit('/student/schedule')
            ->waitFor('[dusk="weekly-grid"]', 10)
            ->assertPresent('[dusk="weekly-grid"]')
            ->assertVisible('[dusk="weekly-grid"]')
            ->assertMissing('.sch-list')
            ->assertSee($fixture['subject']->name)
            ->assertSee($fixture['professor']->user->name)
            ->assertSee($fixture['classroom']->identifier)
            ->assertSee(substr($fixture['schedule']->start_time, 0, 5));
    });
});

// ---------------------------------------------------------------------------
// UC-QA-02 — viewport mobile muestra ScheduleListView en vez del grid
// ---------------------------------------------------------------------------

test('UC-QA-02: mismo tipo de estudiante en viewport mobile ve la lista agrupada por día en vez del grid', function () {
    $fixture = studentScheduleFixture();

    $this->browse(function (Browser $browser) use ($fixture) {
        $browser->resize(375, 812)
            ->loginAs($fixture['student']->user)
            ->visit('/student/schedule')
            ->waitFor('.sch-list', 10)
            ->assertVisible('.sch-list')
            ->assertMissing('[dusk="weekly-grid"]')
            ->assertSee($fixture['subject']->name)
            ->assertSee($fixture['professor']->user->name);
    });
});

// ---------------------------------------------------------------------------
// UC-QA-03 (mitad frontend) — estado vacío sin período activo / sin enrollment
// confirmado: sin error, grid/lista se muestran vacíos.
// Agregado en auditoría de Tasks 4/5 (2026-09-13) — qa.md lo marcaba "pendiente".
// ---------------------------------------------------------------------------

test('UC-QA-03: estudiante sin periodo activo ve estado vacio sin error en desktop', function () {
    $student = Student::factory()->create();

    $this->browse(function (Browser $browser) use ($student) {
        $browser->resize(1440, 900)
            ->loginAs($student->user)
            ->visit('/student/schedule')
            ->waitFor('[dusk="weekly-grid"]', 10)
            ->assertSee('Mi horario')
            ->assertMissing('[dusk="schedule-event-card"]')
            ->assertDontSee('Whoops')
            ->assertDontSee('Server Error');
    });
});

test('UC-QA-03: estudiante sin enrollment confirmado ve estado vacio sin error en mobile', function () {
    Period::factory()->active()->create();
    $student = Student::factory()->create();

    $this->browse(function (Browser $browser) use ($student) {
        $browser->resize(375, 812)
            ->loginAs($student->user)
            ->visit('/student/schedule')
            ->waitFor('.sch-list', 10)
            ->assertSee('Sin clases')
            ->assertMissing('.list-row')
            ->assertDontSee('Whoops')
            ->assertDontSee('Server Error');
    });
});

// ---------------------------------------------------------------------------
// UC-QA-07 (mitad frontend) — estudiante primary/secondary accede sin bloqueo
// ---------------------------------------------------------------------------

test('UC-QA-07: estudiante de nivel primary accede sin bloqueo y ve el grid en desktop', function () {
    $student = Student::factory()->primary()->create();
    $fixture = studentScheduleFixture($student);

    $this->browse(function (Browser $browser) use ($fixture) {
        $browser->resize(1440, 900)
            ->loginAs($fixture['student']->user)
            ->visit('/student/schedule')
            ->waitFor('[dusk="weekly-grid"]', 10)
            ->assertSee($fixture['subject']->name);
    });
});

test('UC-QA-07: estudiante de nivel secondary accede sin bloqueo y ve el grid en desktop', function () {
    $student = Student::factory()->secondary()->create();
    $fixture = studentScheduleFixture($student);

    $this->browse(function (Browser $browser) use ($fixture) {
        $browser->resize(1440, 900)
            ->loginAs($fixture['student']->user)
            ->visit('/student/schedule')
            ->waitFor('[dusk="weekly-grid"]', 10)
            ->assertSee($fixture['subject']->name);
    });
});

// ---------------------------------------------------------------------------
// UC-QA-08 — click sobre un evento no dispara navegación ni popover
// ---------------------------------------------------------------------------

test('UC-QA-08: click sobre un evento del grid en desktop no dispara navegación ni popover', function () {
    $fixture = studentScheduleFixture();

    $this->browse(function (Browser $browser) use ($fixture) {
        $browser->resize(1440, 900)
            ->loginAs($fixture['student']->user)
            ->visit('/student/schedule')
            ->waitFor('[dusk="schedule-event-card"]', 10)
            ->assertMissing('[dusk="list-edit-btn"]')
            ->assertMissing('[dusk="list-delete-btn"]')
            ->click('[dusk="schedule-event-card"]')
            ->pause(800)
            ->assertPathIs('/student/schedule')
            ->assertMissing('.pop-panel')
            ->assertMissing('.pop-scrim');
    });
});

test('UC-QA-08: click sobre un evento de la lista en mobile no dispara navegación ni popover', function () {
    $fixture = studentScheduleFixture();

    $this->browse(function (Browser $browser) use ($fixture) {
        $browser->resize(375, 812)
            ->loginAs($fixture['student']->user)
            ->visit('/student/schedule')
            ->waitFor('.list-row', 10)
            ->assertMissing('[dusk="list-edit-btn"]')
            ->assertMissing('[dusk="list-delete-btn"]')
            ->click('.list-row')
            ->pause(800)
            ->assertPathIs('/student/schedule')
            ->assertMissing('.pop-panel')
            ->assertMissing('.pop-scrim');
    });
});

// ---------------------------------------------------------------------------
// UC-QA-06 — link "Ver horario completo →" del dashboard navega a /student/schedule
// (contrato de Task 3 — tester [pre]; DEBE estar en ROJO hasta que el implementer
// agregue el link con `dusk="view-full-schedule-link"` a student/Dashboard.vue)
// ---------------------------------------------------------------------------

test('UC-QA-06: link "Ver horario completo →" del dashboard navega a /student/schedule', function () {
    $fixture = studentScheduleFixture();

    $this->browse(function (Browser $browser) use ($fixture) {
        $browser->resize(1440, 900)
            ->loginAs($fixture['student']->user)
            ->visit('/student/dashboard')
            ->waitFor('[dusk="view-full-schedule-link"]', 10)
            ->assertSeeIn('[dusk="view-full-schedule-link"]', 'Ver horario completo')
            ->click('[dusk="view-full-schedule-link"]')
            ->waitForLocation('/student/schedule')
            ->assertPathIs('/student/schedule')
            ->waitFor('[dusk="weekly-grid"]', 10)
            ->assertSee($fixture['subject']->name);
    });
});
