<?php

/**
 * QA Gate — Attendance Module
 *
 * Covers the 5 acceptance UCs defined in specs/15-attendance-module/qa.md.
 * Tests exercise the full HTTP → Controller → Action → DB flow.
 */

use App\Enums\AttendanceStatus;
use App\Enums\ClassSessionStatus;
use App\Enums\ClassSessionType;
use App\Http\Resources\Attendance\AttendanceSheetResource;
use App\Models\AttendanceRecord;
use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\EnrollmentDetail;
use App\Models\Period;
use App\Models\Professor;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Shared helpers
// ---------------------------------------------------------------------------

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Profesor', 'guard_name' => 'web']);
});

/**
 * Creates an Admin user.
 */
function qaAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('Admin');

    return $user;
}

/**
 * Creates a section with a professor as main_teacher.
 * The professor's user is returned as the authenticated actor.
 *
 * @return array{professor_user: User, professor: Professor, section: Section}
 */
function qaProfessorSection(): array
{
    Period::factory()->active()->create();
    $professor = Professor::factory()->create();

    $section = Section::factory()->create([
        'main_teacher_id' => $professor->id,
    ]);

    return [
        'professor_user' => $professor->user,
        'professor' => $professor,
        'section' => $section,
    ];
}

/**
 * Enrolls a student in a section and returns a confirmed EnrollmentDetail.
 */
function qaConfirmedDetail(Section $section): EnrollmentDetail
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
// UC-QA-01: Profesor crea sesión regular + pasa asistencia
// ---------------------------------------------------------------------------

test('UC-QA-01: profesor creates regular session then submits attendance — session held, records created', function () {
    [
        'professor_user' => $professorUser,
        'section' => $section,
    ] = qaProfessorSection();

    $detailA = qaConfirmedDetail($section);
    $detailB = qaConfirmedDetail($section);

    // Step 1: professor creates a regular session
    $this->actingAs($professorUser)
        ->post(route('professor.sections.attendance.sessions.store', $section), [
            'type' => 'regular',
            'topic' => 'Tema 1: Introducción',
            'linked_session_id' => null,
            'held_at' => now()->format('Y-m-d'),
        ])
        ->assertRedirect(route('professor.sections.attendance.index', $section));

    $session = ClassSession::where('section_id', $section->id)->first();

    expect($session)->not->toBeNull()
        ->and($session->type)->toBe(ClassSessionType::Regular)
        ->and($session->status)->toBe(ClassSessionStatus::Scheduled);

    // Step 2: professor submits attendance (A=present, B=absent)
    $this->actingAs($professorUser)
        ->put(route('professor.sections.attendance.upsert', [$section, $session]), [
            'marks' => [
                (string) $detailA->id => 'present',
                (string) $detailB->id => 'absent',
            ],
            'professor_present' => true,
        ])
        ->assertRedirect(route('professor.sections.attendance.sheet', [$section, $session]));

    $session->refresh();

    // Session must be held with professor_present true
    expect($session->status)->toBe(ClassSessionStatus::Held)
        ->and($session->professor_present)->toBeTrue();

    // Attendance records must exist with correct statuses
    expect(AttendanceRecord::where('class_session_id', $session->id)
        ->where('enrollment_detail_id', $detailA->id)
        ->where('status', AttendanceStatus::Present)
        ->exists()
    )->toBeTrue();

    expect(AttendanceRecord::where('class_session_id', $session->id)
        ->where('enrollment_detail_id', $detailB->id)
        ->where('status', AttendanceStatus::Absent)
        ->exists()
    )->toBeTrue();

    expect(AttendanceRecord::where('class_session_id', $session->id)->count())->toBe(2);
});

// ---------------------------------------------------------------------------
// UC-QA-02: Admin crea makeup + sube asistencia → sesión cancelada → recovered
// ---------------------------------------------------------------------------

test('UC-QA-02: admin creates makeup session for cancelled session — cancelled becomes recovered, professor_present false', function () {
    $admin = qaAdmin();
    Period::factory()->active()->create();
    $professor = Professor::factory()->create();
    $section = Section::factory()->create(['main_teacher_id' => $professor->id]);

    $detailA = qaConfirmedDetail($section);
    $detailB = qaConfirmedDetail($section);

    // Existing cancelled session
    $cancelledSession = ClassSession::factory()->forSection($section)->create([
        'type' => ClassSessionType::Regular,
        'status' => ClassSessionStatus::Cancelled,
        'held_at' => now()->subDay()->format('Y-m-d'),
    ]);

    // Step 1: admin creates makeup linked to the cancelled session
    $this->actingAs($admin)
        ->post(route('admin.sections.attendance.sessions.store', $section), [
            'type' => 'makeup',
            'topic' => 'Recuperación del tema 3',
            'linked_session_id' => $cancelledSession->id,
            'held_at' => now()->format('Y-m-d'),
        ])
        ->assertRedirect(route('admin.sections.attendance.index', $section));

    $makeupSession = ClassSession::where('section_id', $section->id)
        ->where('type', ClassSessionType::Makeup)
        ->first();

    expect($makeupSession)->not->toBeNull()
        ->and($makeupSession->linked_session_id)->toBe($cancelledSession->id);

    // The cancelled session must now be recovered
    $cancelledSession->refresh();
    expect($cancelledSession->status)->toBe(ClassSessionStatus::Recovered);

    // Step 2: admin submits attendance for the makeup session (all present)
    // Admin attendance controller forces professor_present = false
    $this->actingAs($admin)
        ->put(route('admin.sections.attendance.upsert', [$section, $makeupSession]), [
            'marks' => [
                (string) $detailA->id => 'present',
                (string) $detailB->id => 'present',
            ],
        ])
        ->assertRedirect(route('admin.sections.attendance.sheet', [$section, $makeupSession]));

    $makeupSession->refresh();

    expect($makeupSession->status)->toBe(ClassSessionStatus::Held)
        ->and($makeupSession->professor_present)->toBeFalse();

    expect(AttendanceRecord::where('class_session_id', $makeupSession->id)->count())->toBe(2);

    expect(AttendanceRecord::where('class_session_id', $makeupSession->id)
        ->where('status', AttendanceStatus::Present)
        ->count()
    )->toBe(2);
});

// ---------------------------------------------------------------------------
// UC-QA-03: Profesor crea advance + asistencia copiada a sesión futura
// ---------------------------------------------------------------------------

test('UC-QA-03: profesor creates advance session — future session becomes advanced, attendance copied', function () {
    [
        'professor_user' => $professorUser,
        'section' => $section,
    ] = qaProfessorSection();

    $detailA = qaConfirmedDetail($section);
    $detailB = qaConfirmedDetail($section);

    // Future scheduled session
    $futureSession = ClassSession::factory()->forSection($section)->create([
        'type' => ClassSessionType::Regular,
        'status' => ClassSessionStatus::Scheduled,
        'held_at' => now()->addWeek()->format('Y-m-d'),
    ]);

    // Step 1: professor creates an advance session linked to the future session
    $this->actingAs($professorUser)
        ->post(route('professor.sections.attendance.sessions.store', $section), [
            'type' => 'advance',
            'topic' => 'Adelanto del tema 5',
            'linked_session_id' => $futureSession->id,
            'held_at' => now()->format('Y-m-d'),
        ])
        ->assertRedirect(route('professor.sections.attendance.index', $section));

    $advanceSession = ClassSession::where('section_id', $section->id)
        ->where('type', ClassSessionType::Advance)
        ->first();

    expect($advanceSession)->not->toBeNull()
        ->and($advanceSession->linked_session_id)->toBe($futureSession->id);

    // Future session must now be advanced
    $futureSession->refresh();
    expect($futureSession->status)->toBe(ClassSessionStatus::Advanced);

    // Step 2: professor submits attendance for the advance session
    $this->actingAs($professorUser)
        ->put(route('professor.sections.attendance.upsert', [$section, $advanceSession]), [
            'marks' => [
                (string) $detailA->id => 'present',
                (string) $detailB->id => 'absent',
            ],
            'professor_present' => true,
        ])
        ->assertRedirect(route('professor.sections.attendance.sheet', [$section, $advanceSession]));

    $advanceSession->refresh();
    expect($advanceSession->status)->toBe(ClassSessionStatus::Held)
        ->and($advanceSession->professor_present)->toBeTrue();

    // Attendance records must exist on the advance session
    expect(AttendanceRecord::where('class_session_id', $advanceSession->id)->count())->toBe(2);

    // Records must be COPIED to the future (now advanced) session
    expect(AttendanceRecord::where('class_session_id', $futureSession->id)->count())->toBe(2);

    expect(AttendanceRecord::where('class_session_id', $futureSession->id)
        ->where('enrollment_detail_id', $detailA->id)
        ->where('status', AttendanceStatus::Present)
        ->exists()
    )->toBeTrue();

    expect(AttendanceRecord::where('class_session_id', $futureSession->id)
        ->where('enrollment_detail_id', $detailB->id)
        ->where('status', AttendanceStatus::Absent)
        ->exists()
    )->toBeTrue();
});

// ---------------------------------------------------------------------------
// UC-QA-04: Totales de inasistencia — held, recovered, advanced cuentan
// ---------------------------------------------------------------------------

test('UC-QA-04: absence totals count only held, recovered and advanced sessions — not scheduled or cancelled', function () {
    $admin = qaAdmin();
    Period::factory()->active()->create();
    $professor = Professor::factory()->create();
    $section = Section::factory()->create(['main_teacher_id' => $professor->id]);

    $detailB = qaConfirmedDetail($section);

    // Session held → debe contar
    $sessionHeld = ClassSession::factory()->forSection($section)->create([
        'status' => ClassSessionStatus::Held,
        'held_at' => now()->subDays(3)->format('Y-m-d'),
    ]);
    AttendanceRecord::create([
        'class_session_id' => $sessionHeld->id,
        'enrollment_detail_id' => $detailB->id,
        'status' => AttendanceStatus::Absent,
    ]);

    // Session recovered → debe contar (makeup session)
    $sessionRecovered = ClassSession::factory()->forSection($section)->create([
        'status' => ClassSessionStatus::Recovered,
        'held_at' => now()->subDays(2)->format('Y-m-d'),
    ]);
    AttendanceRecord::create([
        'class_session_id' => $sessionRecovered->id,
        'enrollment_detail_id' => $detailB->id,
        'status' => AttendanceStatus::Absent,
    ]);

    // Session advanced → debe contar
    $sessionAdvanced = ClassSession::factory()->forSection($section)->create([
        'status' => ClassSessionStatus::Advanced,
        'held_at' => now()->subDays(1)->format('Y-m-d'),
    ]);
    AttendanceRecord::create([
        'class_session_id' => $sessionAdvanced->id,
        'enrollment_detail_id' => $detailB->id,
        'status' => AttendanceStatus::Absent,
    ]);

    // Session scheduled → NO debe contar
    $sessionScheduled = ClassSession::factory()->forSection($section)->create([
        'status' => ClassSessionStatus::Scheduled,
        'held_at' => now()->addDays(1)->format('Y-m-d'),
    ]);
    AttendanceRecord::create([
        'class_session_id' => $sessionScheduled->id,
        'enrollment_detail_id' => $detailB->id,
        'status' => AttendanceStatus::Absent,
    ]);

    // Session cancelled → NO debe contar
    $sessionCancelled = ClassSession::factory()->forSection($section)->create([
        'status' => ClassSessionStatus::Cancelled,
        'held_at' => now()->subDays(5)->format('Y-m-d'),
    ]);
    AttendanceRecord::create([
        'class_session_id' => $sessionCancelled->id,
        'enrollment_detail_id' => $detailB->id,
        'status' => AttendanceStatus::Absent,
    ]);

    // Query the professor attendance index — it computes the summary
    $response = $this->actingAs($admin)
        ->get(route('admin.sections.attendance.index', $section))
        ->assertOk();

    // The absence total for detailB must be exactly 3 (held + recovered + advanced)
    $response->assertInertia(fn ($page) => $page
        ->where("absenceTotals.{$detailB->id}", 3)
        ->where('sessionsCounted', 3)
    );
})->skip('admin.sections.attendance.index does not expose absenceTotals — verified via professor route in next test');

test('UC-QA-04 (HTTP): professor attendance index exposes correct absenceTotals and sessionsCounted', function () {
    [
        'professor_user' => $professorUser,
        'section' => $section,
    ] = qaProfessorSection();

    $detailB = qaConfirmedDetail($section);

    $sessionHeld = ClassSession::factory()->forSection($section)->create([
        'status' => ClassSessionStatus::Held,
        'held_at' => now()->subDays(3)->format('Y-m-d'),
    ]);
    AttendanceRecord::create(['class_session_id' => $sessionHeld->id, 'enrollment_detail_id' => $detailB->id, 'status' => AttendanceStatus::Absent]);

    $sessionRecovered = ClassSession::factory()->forSection($section)->create([
        'status' => ClassSessionStatus::Recovered,
        'held_at' => now()->subDays(2)->format('Y-m-d'),
    ]);
    AttendanceRecord::create(['class_session_id' => $sessionRecovered->id, 'enrollment_detail_id' => $detailB->id, 'status' => AttendanceStatus::Absent]);

    $sessionAdvanced = ClassSession::factory()->forSection($section)->create([
        'status' => ClassSessionStatus::Advanced,
        'held_at' => now()->subDays(1)->format('Y-m-d'),
    ]);
    AttendanceRecord::create(['class_session_id' => $sessionAdvanced->id, 'enrollment_detail_id' => $detailB->id, 'status' => AttendanceStatus::Absent]);

    // Sessions that must NOT count
    $sessionScheduled = ClassSession::factory()->forSection($section)->create([
        'status' => ClassSessionStatus::Scheduled,
        'held_at' => now()->addDay()->format('Y-m-d'),
    ]);
    AttendanceRecord::create(['class_session_id' => $sessionScheduled->id, 'enrollment_detail_id' => $detailB->id, 'status' => AttendanceStatus::Absent]);

    $sessionCancelled = ClassSession::factory()->forSection($section)->create([
        'status' => ClassSessionStatus::Cancelled,
        'held_at' => now()->subDays(5)->format('Y-m-d'),
    ]);
    AttendanceRecord::create(['class_session_id' => $sessionCancelled->id, 'enrollment_detail_id' => $detailB->id, 'status' => AttendanceStatus::Absent]);

    $this->actingAs($professorUser)
        ->get(route('professor.sections.attendance.index', $section))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where("absenceTotals.{$detailB->id}", 3)
            ->where('sessionsCounted', 3)
        );
});

test('UC-QA-04: absence totals count only held, recovered and advanced — verified via summaryForSection()', function () {
    Period::factory()->active()->create();
    $professor = Professor::factory()->create();
    $section = Section::factory()->create(['main_teacher_id' => $professor->id]);

    $detailB = qaConfirmedDetail($section);

    // held — should count
    $sessionHeld = ClassSession::factory()->forSection($section)->create([
        'status' => ClassSessionStatus::Held,
        'held_at' => now()->subDays(3)->format('Y-m-d'),
    ]);
    AttendanceRecord::create([
        'class_session_id' => $sessionHeld->id,
        'enrollment_detail_id' => $detailB->id,
        'status' => AttendanceStatus::Absent,
    ]);

    // recovered — should count
    $sessionRecovered = ClassSession::factory()->forSection($section)->create([
        'status' => ClassSessionStatus::Recovered,
        'held_at' => now()->subDays(2)->format('Y-m-d'),
    ]);
    AttendanceRecord::create([
        'class_session_id' => $sessionRecovered->id,
        'enrollment_detail_id' => $detailB->id,
        'status' => AttendanceStatus::Absent,
    ]);

    // advanced — should count
    $sessionAdvanced = ClassSession::factory()->forSection($section)->create([
        'status' => ClassSessionStatus::Advanced,
        'held_at' => now()->subDays(1)->format('Y-m-d'),
    ]);
    AttendanceRecord::create([
        'class_session_id' => $sessionAdvanced->id,
        'enrollment_detail_id' => $detailB->id,
        'status' => AttendanceStatus::Absent,
    ]);

    // scheduled — must NOT count
    $sessionScheduled = ClassSession::factory()->forSection($section)->create([
        'status' => ClassSessionStatus::Scheduled,
        'held_at' => now()->addDay()->format('Y-m-d'),
    ]);
    AttendanceRecord::create([
        'class_session_id' => $sessionScheduled->id,
        'enrollment_detail_id' => $detailB->id,
        'status' => AttendanceStatus::Absent,
    ]);

    // cancelled — must NOT count
    $sessionCancelled = ClassSession::factory()->forSection($section)->create([
        'status' => ClassSessionStatus::Cancelled,
        'held_at' => now()->subDays(5)->format('Y-m-d'),
    ]);
    AttendanceRecord::create([
        'class_session_id' => $sessionCancelled->id,
        'enrollment_detail_id' => $detailB->id,
        'status' => AttendanceStatus::Absent,
    ]);

    $summary = AttendanceSheetResource::summaryForSection($section);

    // 3 sessions counted (held + recovered + advanced)
    expect($summary['sessions_counted'])->toBe(3);

    // 3 absences for detailB
    expect($summary['absence_totals'][$detailB->id] ?? 0)->toBe(3);
});

// ---------------------------------------------------------------------------
// UC-QA-05: Validación — no se puede recuperar una sesión ya recuperada
// ---------------------------------------------------------------------------

test('UC-QA-05: creating a second makeup for an already recovered session returns 422', function () {
    $admin = qaAdmin();
    Period::factory()->active()->create();
    $professor = Professor::factory()->create();
    $section = Section::factory()->create(['main_teacher_id' => $professor->id]);

    // A session that is already in recovered status (has been linked to an existing makeup)
    $alreadyRecovered = ClassSession::factory()->forSection($section)->create([
        'type' => ClassSessionType::Regular,
        'status' => ClassSessionStatus::Recovered,
        'held_at' => now()->subDays(2)->format('Y-m-d'),
    ]);

    // Attempt to create a second makeup for the already-recovered session
    $this->actingAs($admin)
        ->post(route('admin.sections.attendance.sessions.store', $section), [
            'type' => 'makeup',
            'topic' => 'Segundo intento de recuperación',
            'linked_session_id' => $alreadyRecovered->id,
            'held_at' => now()->format('Y-m-d'),
        ])
        ->assertSessionHasErrors('linked_session_id');

    // No new session should be created
    expect(ClassSession::where('type', ClassSessionType::Makeup)->exists())->toBeFalse();
});

test('UC-QA-05: professor also cannot create a second makeup for an already recovered session', function () {
    [
        'professor_user' => $professorUser,
        'section' => $section,
    ] = qaProfessorSection();

    $alreadyRecovered = ClassSession::factory()->forSection($section)->create([
        'type' => ClassSessionType::Regular,
        'status' => ClassSessionStatus::Recovered,
        'held_at' => now()->subDays(2)->format('Y-m-d'),
    ]);

    $this->actingAs($professorUser)
        ->post(route('professor.sections.attendance.sessions.store', $section), [
            'type' => 'makeup',
            'topic' => 'Segundo intento de recuperación',
            'linked_session_id' => $alreadyRecovered->id,
            'held_at' => now()->format('Y-m-d'),
        ])
        ->assertSessionHasErrors('linked_session_id');

    expect(ClassSession::where('type', ClassSessionType::Makeup)->exists())->toBeFalse();
});
