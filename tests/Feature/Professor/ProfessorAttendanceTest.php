<?php

use App\Enums\AttendanceStatus;
use App\Enums\ClassSessionStatus;
use App\Enums\ClassSessionType;
use App\Enums\EnrollmentDetailStatus;
use App\Models\AttendanceRecord;
use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\EnrollmentDetail;
use App\Models\Period;
use App\Models\Professor;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

/**
 * Creates a base context: professor owns the section.
 *
 * @return array{professor: Professor, section: Section}
 */
function attendanceContext(): array
{
    $professor = Professor::factory()->create();
    $period = Period::factory()->active()->create();
    $section = Section::factory()->create([
        'period_id' => $period->id,
        'main_teacher_id' => $professor->id,
    ]);

    return compact('professor', 'section', 'period');
}

/**
 * Creates a confirmed enrollment detail for the given section.
 *
 * Uses the section's period_id so the enrollment belongs to the same period.
 */
function confirmedDetail(Section $section): EnrollmentDetail
{
    $student = Student::factory()->create();
    $enrollment = Enrollment::factory()->create([
        'student_id' => $student->id,
        'period_id' => $section->period_id,
    ]);

    return EnrollmentDetail::factory()->confirmed()->create([
        'enrollment_id' => $enrollment->id,
        'section_id' => $section->id,
    ]);
}

// ---------------------------------------------------------------------------
// index — GET /professor/sections/{section}/attendance
// ---------------------------------------------------------------------------

test('unauthenticated users are redirected from attendance index', function () {
    $section = Section::factory()->create();

    $this->get(route('professor.sections.attendance.index', $section))
        ->assertRedirect(route('login'));
});

test('professor can view attendance index for their section', function () {
    ['professor' => $professor, 'section' => $section] = attendanceContext();

    ClassSession::factory()->forSection($section)->count(2)->create();

    $this->actingAs($professor->user)
        ->get(route('professor.sections.attendance.index', $section))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('professor/attendance/Index')
            ->has('section')
            ->has('sessions')
            ->has('period')
        );
});

test('professor cannot view attendance index for another professors section', function () {
    ['section' => $section] = attendanceContext();
    $other = Professor::factory()->create();

    $this->actingAs($other->user)
        ->get(route('professor.sections.attendance.index', $section))
        ->assertForbidden();
});

test('attendance index returns correct session structure', function () {
    ['professor' => $professor, 'section' => $section] = attendanceContext();

    $session = ClassSession::factory()->forSection($section)->create([
        'type' => ClassSessionType::Regular,
        'status' => ClassSessionStatus::Scheduled,
        'topic' => 'Derivadas',
    ]);

    $this->actingAs($professor->user)
        ->get(route('professor.sections.attendance.index', $section))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('sessions.data', 1)
            ->has('sessions.data.0', fn ($s) => $s
                ->where('id', $session->id)
                ->where('type', 'regular')
                ->where('status', 'scheduled')
                ->where('topic', 'Derivadas')
                ->where('has_record', false)
                ->etc()
            )
        );
});

// ---------------------------------------------------------------------------
// storeSession — POST /professor/sections/{section}/attendance/sessions
// ---------------------------------------------------------------------------

test('professor can create a regular class session', function () {
    ['professor' => $professor, 'section' => $section] = attendanceContext();

    $this->actingAs($professor->user)
        ->post(route('professor.sections.attendance.sessions.store', $section), [
            'type' => 'regular',
            'topic' => 'Introducción al cálculo',
            'linked_session_id' => null,
            'held_at' => null,
        ])
        ->assertRedirect(route('professor.sections.attendance.index', $section));

    expect(ClassSession::where('section_id', $section->id)->where('type', ClassSessionType::Regular)->exists())
        ->toBeTrue();
});

test('professor can create a makeup session linked to a cancelled session', function () {
    ['professor' => $professor, 'section' => $section] = attendanceContext();

    $cancelled = ClassSession::factory()->forSection($section)->create([
        'type' => ClassSessionType::Regular,
        'status' => ClassSessionStatus::Cancelled,
    ]);

    $this->actingAs($professor->user)
        ->post(route('professor.sections.attendance.sessions.store', $section), [
            'type' => 'makeup',
            'linked_session_id' => $cancelled->id,
            'topic' => 'Recuperación',
            'held_at' => null,
        ])
        ->assertRedirect(route('professor.sections.attendance.index', $section));

    expect(ClassSession::where('section_id', $section->id)->where('type', ClassSessionType::Makeup)->exists())
        ->toBeTrue();

    $cancelled->refresh();
    expect($cancelled->status)->toBe(ClassSessionStatus::Recovered);
});

test('professor can create an advance session linked to a future session', function () {
    ['professor' => $professor, 'section' => $section] = attendanceContext();

    $future = ClassSession::factory()->forSection($section)->create([
        'type' => ClassSessionType::Regular,
        'status' => ClassSessionStatus::Scheduled,
    ]);

    $this->actingAs($professor->user)
        ->post(route('professor.sections.attendance.sessions.store', $section), [
            'type' => 'advance',
            'linked_session_id' => $future->id,
            'topic' => 'Clase adelantada',
            'held_at' => null,
        ])
        ->assertRedirect(route('professor.sections.attendance.index', $section));

    expect(ClassSession::where('section_id', $section->id)->where('type', ClassSessionType::Advance)->exists())
        ->toBeTrue();

    $future->refresh();
    expect($future->status)->toBe(ClassSessionStatus::Advanced);
});

test('another professor cannot create sessions in a section they do not own', function () {
    ['section' => $section] = attendanceContext();
    $other = Professor::factory()->create();

    $this->actingAs($other->user)
        ->post(route('professor.sections.attendance.sessions.store', $section), [
            'type' => 'regular',
        ])
        ->assertForbidden();
});

test('storeSession validates required type field', function () {
    ['professor' => $professor, 'section' => $section] = attendanceContext();

    $this->actingAs($professor->user)
        ->post(route('professor.sections.attendance.sessions.store', $section), [])
        ->assertSessionHasErrors('type');
});

test('storeSession rejects invalid type values', function () {
    ['professor' => $professor, 'section' => $section] = attendanceContext();

    $this->actingAs($professor->user)
        ->post(route('professor.sections.attendance.sessions.store', $section), [
            'type' => 'invalid_type',
        ])
        ->assertSessionHasErrors('type');
});

// ---------------------------------------------------------------------------
// sheet — GET /professor/sections/{section}/attendance/sessions/{classSession}
// ---------------------------------------------------------------------------

test('professor can view the attendance sheet for a session in their section', function () {
    ['professor' => $professor, 'section' => $section] = attendanceContext();

    $session = ClassSession::factory()->forSection($section)->create();
    confirmedDetail($section);

    $this->actingAs($professor->user)
        ->get(route('professor.sections.attendance.sheet', [$section, $session]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('professor/attendance/Sheet')
            ->has('sheet')
            ->has('sheet.session')
            ->has('sheet.roster')
            ->has('sheet.absence_totals')
            ->has('sheet.sessions_counted')
        );
});

test('sheet roster includes only confirmed enrollment details', function () {
    ['professor' => $professor, 'section' => $section] = attendanceContext();

    $session = ClassSession::factory()->forSection($section)->create();

    // Confirmed detail — should appear
    confirmedDetail($section);

    // Draft detail — should NOT appear
    $student2 = Student::factory()->create();
    $enrollment2 = Enrollment::factory()->create(['student_id' => $student2->id]);
    EnrollmentDetail::factory()->create([
        'enrollment_id' => $enrollment2->id,
        'section_id' => $section->id,
        'status' => EnrollmentDetailStatus::Draft,
    ]);

    $this->actingAs($professor->user)
        ->get(route('professor.sections.attendance.sheet', [$section, $session]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('sheet.roster', 1)
        );
});

test('sheet roster shows null status when no attendance record exists yet', function () {
    ['professor' => $professor, 'section' => $section] = attendanceContext();

    $session = ClassSession::factory()->forSection($section)->create();
    confirmedDetail($section);

    $this->actingAs($professor->user)
        ->get(route('professor.sections.attendance.sheet', [$section, $session]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('sheet.roster.0', fn ($r) => $r
                ->where('status', null)
                ->etc()
            )
        );
});

test('sheet absence_totals counts absences from held and advanced sessions only', function () {
    ['professor' => $professor, 'section' => $section] = attendanceContext();

    $detail = confirmedDetail($section);

    // Held session — absence counts
    $heldSession = ClassSession::factory()->forSection($section)->create([
        'status' => ClassSessionStatus::Held,
    ]);
    AttendanceRecord::create([
        'class_session_id' => $heldSession->id,
        'enrollment_detail_id' => $detail->id,
        'status' => AttendanceStatus::Absent,
    ]);

    // Recovered session — absence does NOT count
    $recoveredSession = ClassSession::factory()->forSection($section)->create([
        'status' => ClassSessionStatus::Recovered,
    ]);
    AttendanceRecord::create([
        'class_session_id' => $recoveredSession->id,
        'enrollment_detail_id' => $detail->id,
        'status' => AttendanceStatus::Absent,
    ]);

    // Current session (to call sheet on)
    $currentSession = ClassSession::factory()->forSection($section)->create();

    $this->actingAs($professor->user)
        ->get(route('professor.sections.attendance.sheet', [$section, $currentSession]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('sheet.absence_totals', fn ($totals) => $totals
                ->where((string) $detail->id, 1) // only the held absence
            )
            ->where('sheet.sessions_counted', 1) // only held session counted
        );
});

test('another professor cannot view the attendance sheet for another section', function () {
    ['section' => $section] = attendanceContext();
    $session = ClassSession::factory()->forSection($section)->create();
    $other = Professor::factory()->create();

    $this->actingAs($other->user)
        ->get(route('professor.sections.attendance.sheet', [$section, $session]))
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// upsertAttendance — PUT /professor/sections/{section}/attendance/sessions/{classSession}
// ---------------------------------------------------------------------------

test('professor can save attendance for a session', function () {
    ['professor' => $professor, 'section' => $section] = attendanceContext();

    $session = ClassSession::factory()->forSection($section)->create();
    $detail = confirmedDetail($section);

    $this->actingAs($professor->user)
        ->put(route('professor.sections.attendance.upsert', [$section, $session]), [
            'marks' => [(string) $detail->id => 'present'],
            'professor_present' => true,
        ])
        ->assertRedirect(route('professor.sections.attendance.sheet', [$section, $session]));

    expect(AttendanceRecord::where('class_session_id', $session->id)
        ->where('enrollment_detail_id', $detail->id)
        ->where('status', AttendanceStatus::Present)
        ->exists()
    )->toBeTrue();

    $session->refresh();
    expect($session->status)->toBe(ClassSessionStatus::Held);
});

test('upsertAttendance updates existing records', function () {
    ['professor' => $professor, 'section' => $section] = attendanceContext();

    $session = ClassSession::factory()->forSection($section)->create();
    $detail = confirmedDetail($section);

    AttendanceRecord::create([
        'class_session_id' => $session->id,
        'enrollment_detail_id' => $detail->id,
        'status' => AttendanceStatus::Present,
    ]);

    $this->actingAs($professor->user)
        ->put(route('professor.sections.attendance.upsert', [$section, $session]), [
            'marks' => [(string) $detail->id => 'absent'],
            'professor_present' => false,
        ])
        ->assertRedirect();

    expect(AttendanceRecord::where('class_session_id', $session->id)->count())->toBe(1);

    $record = AttendanceRecord::where('class_session_id', $session->id)->first();
    expect($record->status)->toBe(AttendanceStatus::Absent);

    $session->refresh();
    expect($session->professor_present)->toBeFalse();
});

test('upsertAttendance validates marks is required', function () {
    ['professor' => $professor, 'section' => $section] = attendanceContext();
    $session = ClassSession::factory()->forSection($section)->create();

    $this->actingAs($professor->user)
        ->put(route('professor.sections.attendance.upsert', [$section, $session]), [
            'professor_present' => true,
        ])
        ->assertSessionHasErrors('marks');
});

test('upsertAttendance rejects invalid mark values', function () {
    ['professor' => $professor, 'section' => $section] = attendanceContext();
    $session = ClassSession::factory()->forSection($section)->create();

    $this->actingAs($professor->user)
        ->put(route('professor.sections.attendance.upsert', [$section, $session]), [
            'marks' => ['1' => 'invalid'],
            'professor_present' => true,
        ])
        ->assertSessionHasErrors('marks.1');
});

test('another professor cannot save attendance for another sections session', function () {
    ['section' => $section] = attendanceContext();
    $session = ClassSession::factory()->forSection($section)->create();
    $other = Professor::factory()->create();

    $this->actingAs($other->user)
        ->put(route('professor.sections.attendance.upsert', [$section, $session]), [
            'marks' => [],
            'professor_present' => true,
        ])
        ->assertForbidden();
});
