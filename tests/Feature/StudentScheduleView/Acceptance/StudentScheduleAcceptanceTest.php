<?php

/**
 * Acceptance tests — 21-student-schedule-view (Task 1: backend)
 *
 * These tests define the behavioural contract for `Student\ScheduleController@index`,
 * the route `student.schedule.index` and `StudentScheduleResource`. They MUST be RED
 * before implementation (route/controller/resource do not exist yet) and GREEN after.
 * The implementer MUST NOT modify this file.
 *
 * Contract (specs/21-student-schedule-view/requirements.md + design.md):
 * - GET route('student.schedule.index') renders Inertia component 'student/Schedule'
 *   with a `schedules` prop shaped exactly like the `Schedule[]` consumed by
 *   WeeklyGrid.vue / ScheduleListView.vue (resources/js/types/scheduling.ts:178-193),
 *   mirroring App\Http\Resources\Scheduling\ScheduleResource's shape 1:1.
 * - Only EnrollmentDetail::status = confirmed sections are included.
 * - Only Schedule rows currently in validity window (valid_from <= today AND
 *   (valid_until is null OR valid_until >= today)) are included.
 * - The student is always resolved via $request->user()->student (no {student} param).
 * - No period active, or no Enrollment for the active period → empty `schedules`, HTTP 200.
 * - Admin / Profesor / Representante hitting the route → 403 (outside role:Estudiante group).
 * - No restriction by educational_level (primary/secondary/university all allowed).
 *
 * UCs covered here (specs/21-student-schedule-view/qa.md): UC-QA-01 (backend half),
 * UC-QA-03, UC-QA-04, UC-QA-05, UC-QA-07 (backend half), UC-QA-09.
 * UC-QA-02/06/08 are frontend/Dusk-only (Task 2/3/5), not covered in this file.
 */

use App\Enums\EnrollmentDetailStatus;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\EnrollmentDetail;
use App\Models\Guardian;
use App\Models\Period;
use App\Models\Professor;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
 * Crea un Enrollment (cabecera, cualquier status) para el estudiante en el período dado,
 * si todavía no existe uno.
 */
function scheduleViewEnrollmentFor(Student $student, Period $period): Enrollment
{
    return Enrollment::where('student_id', $student->id)
        ->where('period_id', $period->id)
        ->first() ?? Enrollment::factory()->confirmed()->create([
            'student_id' => $student->id,
            'period_id' => $period->id,
        ]);
}

/**
 * Crea un EnrollmentDetail con el status indicado, junto con su Section y Schedule
 * (valid_from/valid_until configurables), para el período dado.
 *
 * @return array{detail: EnrollmentDetail, schedule: Schedule, section: Section, subject: Subject, professor: Professor, classroom: Classroom}
 */
function scheduleViewDetailWithSchedule(
    Student $student,
    Period $period,
    EnrollmentDetailStatus $status,
    ?string $validFrom = null,
    ?string $validUntil = null,
): array {
    $enrollment = scheduleViewEnrollmentFor($student, $period);

    $subject = Subject::factory()->create();
    $section = Section::factory()->create(['subject_id' => $subject->id, 'period_id' => $period->id]);
    $professor = Professor::factory()->create();
    $classroom = Classroom::factory()->create();

    $schedule = Schedule::factory()->create([
        'section_id' => $section->id,
        'subject_id' => $subject->id,
        'professor_id' => $professor->id,
        'classroom_id' => $classroom->id,
        'valid_from' => $validFrom ?? now()->subYear()->toDateString(),
        'valid_until' => $validUntil,
    ]);

    $detail = EnrollmentDetail::factory()->create([
        'enrollment_id' => $enrollment->id,
        'subject_id' => $subject->id,
        'section_id' => $section->id,
        'status' => $status,
    ]);

    return compact('detail', 'schedule', 'section', 'subject', 'professor', 'classroom');
}

// ---------------------------------------------------------------------------
// UC-QA-01 (backend half) — shape completo para una clase confirmada y vigente
// ---------------------------------------------------------------------------

test('estudiante con clase confirmada y horario vigente: schedules trae el shape completo', function () {
    $period = Period::factory()->active()->create();
    $student = Student::factory()->create();

    ['schedule' => $schedule, 'section' => $section, 'subject' => $subject, 'professor' => $professor, 'classroom' => $classroom] =
        scheduleViewDetailWithSchedule($student, $period, EnrollmentDetailStatus::Confirmed);

    $this->actingAs($student->user)
        ->get(route('student.schedule.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('student/Schedule')
            ->has('schedules', 1)
            ->where('schedules.0.id', $schedule->id)
            ->where('schedules.0.section.id', $section->id)
            ->where('schedules.0.section.code', $section->code)
            ->where('schedules.0.professor.id', $professor->id)
            ->where('schedules.0.professor.user.name', $professor->user->name)
            ->where('schedules.0.classroom.id', $classroom->id)
            ->where('schedules.0.classroom.identifier', $classroom->identifier)
            ->where('schedules.0.subject.id', $subject->id)
            ->where('schedules.0.subject.name', $subject->name)
            ->where('schedules.0.subject.code', $subject->code)
            ->where('schedules.0.dayOfWeek', $schedule->day_of_week->value)
            ->where('schedules.0.dayLabel', $schedule->day_of_week->label())
            ->where('schedules.0.startTime', substr($schedule->start_time, 0, 5))
            ->where('schedules.0.endTime', substr($schedule->end_time, 0, 5))
            ->where('schedules.0.type', $schedule->type->value)
            ->where('schedules.0.typeLabel', $schedule->type->label())
            ->where('schedules.0.validUntil', null)
            ->has('schedules.0.career')
        );
});

// ---------------------------------------------------------------------------
// UC-QA-04 — solo EnrollmentDetail::confirmed aparece; draft y rejected se excluyen
// ---------------------------------------------------------------------------

test('EnrollmentDetail en draft no aparece en schedules', function () {
    $period = Period::factory()->active()->create();
    $student = Student::factory()->create();

    ['detail' => $confirmedDetail] = scheduleViewDetailWithSchedule($student, $period, EnrollmentDetailStatus::Confirmed);
    ['detail' => $draftDetail] = scheduleViewDetailWithSchedule($student, $period, EnrollmentDetailStatus::Draft);

    $this->actingAs($student->user)
        ->get(route('student.schedule.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('student/Schedule')
            ->has('schedules', 1)
        );

    $this->assertDatabaseHas('enrollment_details', [
        'id' => $draftDetail->id,
        'status' => EnrollmentDetailStatus::Draft->value,
    ]);
    $this->assertDatabaseHas('enrollment_details', [
        'id' => $confirmedDetail->id,
        'status' => EnrollmentDetailStatus::Confirmed->value,
    ]);
});

test('EnrollmentDetail en rejected no aparece en schedules', function () {
    $period = Period::factory()->active()->create();
    $student = Student::factory()->create();

    scheduleViewDetailWithSchedule($student, $period, EnrollmentDetailStatus::Confirmed);
    ['detail' => $rejectedDetail] = scheduleViewDetailWithSchedule($student, $period, EnrollmentDetailStatus::Rejected);

    $this->actingAs($student->user)
        ->get(route('student.schedule.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('student/Schedule')
            ->has('schedules', 1)
        );

    $this->assertDatabaseHas('enrollment_details', [
        'id' => $rejectedDetail->id,
        'status' => EnrollmentDetailStatus::Rejected->value,
    ]);
});

// ---------------------------------------------------------------------------
// UC-QA-05 — filtro de vigencia: valid_until vencido, valid_from futuro
// ---------------------------------------------------------------------------

test('Schedule con valid_until vencido no aparece en schedules', function () {
    $period = Period::factory()->active()->create();
    $student = Student::factory()->create();

    scheduleViewDetailWithSchedule(
        $student,
        $period,
        EnrollmentDetailStatus::Confirmed,
        validFrom: now()->subYears(2)->toDateString(),
        validUntil: now()->subDay()->toDateString(),
    );

    $this->actingAs($student->user)
        ->get(route('student.schedule.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('student/Schedule')
            ->has('schedules', 0)
        );
});

test('Schedule con valid_from futuro no aparece en schedules', function () {
    $period = Period::factory()->active()->create();
    $student = Student::factory()->create();

    scheduleViewDetailWithSchedule(
        $student,
        $period,
        EnrollmentDetailStatus::Confirmed,
        validFrom: now()->addMonth()->toDateString(),
        validUntil: null,
    );

    $this->actingAs($student->user)
        ->get(route('student.schedule.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('student/Schedule')
            ->has('schedules', 0)
        );
});

test('Schedule vigente hoy mismo (valid_until = hoy) SI aparece en schedules', function () {
    $period = Period::factory()->active()->create();
    $student = Student::factory()->create();

    scheduleViewDetailWithSchedule(
        $student,
        $period,
        EnrollmentDetailStatus::Confirmed,
        validFrom: now()->subYear()->toDateString(),
        validUntil: now()->toDateString(),
    );

    $this->actingAs($student->user)
        ->get(route('student.schedule.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('student/Schedule')
            ->has('schedules', 1)
        );
});

// ---------------------------------------------------------------------------
// UC-QA-03 — estado vacío sin período activo o sin Enrollment, sin error
// ---------------------------------------------------------------------------

test('sin periodo activo: schedules vacio, HTTP 200 sin error', function () {
    $student = Student::factory()->create();

    $this->actingAs($student->user)
        ->get(route('student.schedule.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('student/Schedule')
            ->has('schedules', 0)
        );
});

test('con periodo activo pero sin ningun Enrollment del estudiante: schedules vacio', function () {
    Period::factory()->active()->create();
    $student = Student::factory()->create();

    $this->assertDatabaseMissing('enrollments', ['student_id' => $student->id]);

    $this->actingAs($student->user)
        ->get(route('student.schedule.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('student/Schedule')
            ->has('schedules', 0)
        );
});

test('con Enrollment pero sin ningun EnrollmentDetail confirmado: schedules vacio', function () {
    $period = Period::factory()->active()->create();
    $student = Student::factory()->create();

    scheduleViewDetailWithSchedule($student, $period, EnrollmentDetailStatus::Draft);
    scheduleViewDetailWithSchedule($student, $period, EnrollmentDetailStatus::Rejected);

    $this->actingAs($student->user)
        ->get(route('student.schedule.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('student/Schedule')
            ->has('schedules', 0)
        );
});

// ---------------------------------------------------------------------------
// UC-QA-07 (backend half) — sin restriccion por nivel educativo
// ---------------------------------------------------------------------------

test('estudiante de nivel primary accede sin bloqueo y ve su horario', function () {
    $period = Period::factory()->active()->create();
    $student = Student::factory()->primary()->create();

    scheduleViewDetailWithSchedule($student, $period, EnrollmentDetailStatus::Confirmed);

    $this->actingAs($student->user)
        ->get(route('student.schedule.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('student/Schedule')
            ->has('schedules', 1)
        );
});

test('estudiante de nivel secondary accede sin bloqueo y ve su horario', function () {
    $period = Period::factory()->active()->create();
    $student = Student::factory()->secondary()->create();

    scheduleViewDetailWithSchedule($student, $period, EnrollmentDetailStatus::Confirmed);

    $this->actingAs($student->user)
        ->get(route('student.schedule.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('student/Schedule')
            ->has('schedules', 1)
        );
});

// ---------------------------------------------------------------------------
// UC-QA-09 — 403 para Admin, Profesor y Representante
// ---------------------------------------------------------------------------

test('Admin accediendo a student.schedule.index recibe 403', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $this->actingAs($admin)
        ->get(route('student.schedule.index'))
        ->assertForbidden();
});

test('Profesor accediendo a student.schedule.index recibe 403', function () {
    $professor = Professor::factory()->create();

    $this->actingAs($professor->user)
        ->get(route('student.schedule.index'))
        ->assertForbidden();
});

test('Representante accediendo a student.schedule.index recibe 403', function () {
    $guardian = Guardian::factory()->create();

    $this->actingAs($guardian->user)
        ->get(route('student.schedule.index'))
        ->assertForbidden();
});
