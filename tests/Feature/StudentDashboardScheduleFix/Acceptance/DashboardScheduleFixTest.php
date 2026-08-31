<?php

use App\Enums\EnrollmentDetailStatus;
use App\Models\Enrollment;
use App\Models\EnrollmentDetail;
use App\Models\Period;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(fn () => app(PermissionRegistrar::class)->forgetCachedPermissions());

/**
 * Crea una inscripción con un EnrollmentDetail y su horario para el día de
 * la semana indicado. `dayOfWeek` debe ser un caso válido de DayOfWeek
 * (lunes a sábado) — el enum no tiene caso 'sunday' por diseño (ver design.md).
 */
function enrollmentDetailForDay(
    Student $student,
    Period $period,
    string $dayOfWeek,
    EnrollmentDetailStatus $status,
    string $startTime = '08:30:00',
): EnrollmentDetail {
    $enrollment = Enrollment::where('student_id', $student->id)
        ->where('period_id', $period->id)
        ->first() ?? Enrollment::factory()->confirmed()->create([
            'student_id' => $student->id,
            'period_id' => $period->id,
        ]);

    $subject = Subject::factory()->create();
    $section = Section::factory()->create(['subject_id' => $subject->id]);

    [$h, $m] = explode(':', $startTime);
    $endMinutes = (int) $h * 60 + (int) $m + 45;
    $endTime = sprintf('%02d:%02d:00', intdiv($endMinutes, 60), $endMinutes % 60);

    Schedule::factory()->create([
        'section_id' => $section->id,
        'subject_id' => $subject->id,
        'day_of_week' => $dayOfWeek,
        'start_time' => $startTime,
        'end_time' => $endTime,
    ]);

    return EnrollmentDetail::factory()->create([
        'enrollment_id' => $enrollment->id,
        'subject_id' => $subject->id,
        'section_id' => $section->id,
        'status' => $status,
    ]);
}

// RF-01 — domingo no crashea (HLZ-42, ya corregido en commit 2a91c45 fuera del arnés)
// Test de regresión: debe estar en VERDE — confirma que el fix externo sigue vigente.
// El enum DayOfWeek no tiene caso 'sunday' (por diseño, ver design.md): el estudiante
// tiene una materia confirmada un lunes, pero la fecha del sistema es domingo, así que
// el filtro `where('day_of_week', 'sunday')` no encuentra coincidencias de forma natural.
test('RF-01: dashboard un domingo responde 200 con today_schedules vacío', function () {
    $period = Period::factory()->active()->create();
    $student = Student::factory()->create();

    enrollmentDetailForDay($student, $period, 'monday', EnrollmentDetailStatus::Confirmed);

    $this->travelTo(Carbon::parse('2024-01-07 09:00:00')); // domingo

    $this->withoutVite()
        ->actingAs($student->user)
        ->get(route('student.dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('student/Dashboard')
            ->where('today_schedules', [])
            ->has('today_label')
        );
});

// RF-02 — regresión: lunes a sábado sigue funcionando igual (período, inscripción,
// UC del pensum, representantes y horario del día). Debe estar en VERDE.
test('RF-02: dashboard un dia habil calcula periodo, inscripcion, uc y horario del dia sin cambios', function () {
    $this->travelTo(Carbon::parse('2024-01-08 10:00:00')); // lunes

    $period = Period::factory()->active()->create();
    $student = Student::factory()->create();

    $detail = enrollmentDetailForDay($student, $period, 'monday', EnrollmentDetailStatus::Confirmed, '08:30:00');

    $this->withoutVite()
        ->actingAs($student->user)
        ->get(route('student.dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('student/Dashboard')
            ->where('period.name', $period->name)
            ->has('enrollment')
            ->whereNot('enrollment', null)
            ->where('today_label', 'Lunes')
            ->has('today_schedules', 1)
            ->where('today_schedules.0.subject_name', $detail->subject->name)
            ->where('today_schedules.0.section_code', $detail->section->code)
        );

    $this->assertDatabaseHas('enrollment_details', [
        'id' => $detail->id,
        'status' => EnrollmentDetailStatus::Confirmed->value,
    ]);
});

// RF-03 — HLZ-39: today_schedules debe filtrar por confirmedDetails, no por details.
// Pendiente en el código real (DashboardController usa $enrollment->details).
// Debe estar en ROJO hasta que el implementer aplique el cambio de design.md.
test('RF-03: today_schedules solo incluye EnrollmentDetail confirmado, excluye draft', function () {
    $this->travelTo(Carbon::parse('2024-01-08 10:00:00')); // lunes

    $period = Period::factory()->active()->create();
    $student = Student::factory()->create();

    $confirmed = enrollmentDetailForDay($student, $period, 'monday', EnrollmentDetailStatus::Confirmed, '08:30:00');
    $draft = enrollmentDetailForDay($student, $period, 'monday', EnrollmentDetailStatus::Draft, '10:00:00');

    $this->withoutVite()
        ->actingAs($student->user)
        ->get(route('student.dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('student/Dashboard')
            ->has('today_schedules', 1)
            ->where('today_schedules.0.subject_name', $confirmed->subject->name)
        );

    $this->assertDatabaseHas('enrollment_details', [
        'id' => $draft->id,
        'status' => EnrollmentDetailStatus::Draft->value,
    ]);
});

// RF-03 — variante rejected, misma regla que draft.
test('RF-03: today_schedules excluye EnrollmentDetail rechazado', function () {
    $this->travelTo(Carbon::parse('2024-01-08 10:00:00')); // lunes

    $period = Period::factory()->active()->create();
    $student = Student::factory()->create();

    $confirmed = enrollmentDetailForDay($student, $period, 'monday', EnrollmentDetailStatus::Confirmed, '08:30:00');
    $rejected = enrollmentDetailForDay($student, $period, 'monday', EnrollmentDetailStatus::Rejected, '10:00:00');

    $this->withoutVite()
        ->actingAs($student->user)
        ->get(route('student.dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('student/Dashboard')
            ->has('today_schedules', 1)
            ->where('today_schedules.0.subject_name', $confirmed->subject->name)
        );

    $this->assertDatabaseHas('enrollment_details', [
        'id' => $rejected->id,
        'status' => EnrollmentDetailStatus::Rejected->value,
    ]);
});
