<?php

use App\Enums\AttendanceStatus;
use App\Enums\ClassSessionStatus;
use App\Enums\ClassSessionType;
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

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
});

/**
 * Creates an admin user.
 */
function adminAttendanceUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('Admin');

    return $user;
}

/**
 * Creates a section with an active period and a professor.
 *
 * @return array{admin: User, section: Section}
 */
function adminAttendanceContext(): array
{
    $admin = adminAttendanceUser();
    $professor = Professor::factory()->create();
    Period::factory()->active()->create();
    $section = Section::factory()->create([
        'main_teacher_id' => $professor->id,
    ]);

    return compact('admin', 'section');
}

/**
 * Creates a confirmed enrollment detail for the given section.
 */
function adminConfirmedDetail(Section $section): EnrollmentDetail
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
// index — GET /admin/attendance
// ---------------------------------------------------------------------------

test('unauthenticated users are redirected from admin attendance index', function () {
    $this->get(route('admin.attendance.index'))->assertRedirect(route('login'));
});

test('non-admin cannot access admin attendance index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.attendance.index'))->assertForbidden();
});

test('admin can access attendance index and sees pending sessions', function () {
    ['admin' => $admin, 'section' => $section] = adminAttendanceContext();

    // Scheduled session with past held_at — should appear as pending
    ClassSession::factory()->forSection($section)->create([
        'status' => ClassSessionStatus::Scheduled,
        'held_at' => now()->subDay()->format('Y-m-d'),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.attendance.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/attendance/Index')
            ->has('pending_sessions')
        );
});

test('index only shows scheduled sessions with past held_at and no attendance records', function () {
    ['admin' => $admin, 'section' => $section] = adminAttendanceContext();

    // Pending — scheduled, past held_at, no attendance records
    $pendingSession = ClassSession::factory()->forSection($section)->create([
        'status' => ClassSessionStatus::Scheduled,
        'held_at' => now()->subDay()->format('Y-m-d'),
    ]);

    // Not pending — already held
    ClassSession::factory()->forSection($section)->create([
        'status' => ClassSessionStatus::Held,
        'held_at' => now()->subDay()->format('Y-m-d'),
    ]);

    // Not pending — future held_at
    ClassSession::factory()->forSection($section)->create([
        'status' => ClassSessionStatus::Scheduled,
        'held_at' => now()->addDay()->format('Y-m-d'),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.attendance.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('pending_sessions.data', 1)
            ->has('pending_sessions.data.0', fn ($s) => $s
                ->where('id', $pendingSession->id)
                ->etc()
            )
        );
});

test('index excludes sessions that already have attendance records', function () {
    ['admin' => $admin, 'section' => $section] = adminAttendanceContext();

    $detail = adminConfirmedDetail($section);

    $sessionWithRecords = ClassSession::factory()->forSection($section)->create([
        'status' => ClassSessionStatus::Scheduled,
        'held_at' => now()->subDay()->format('Y-m-d'),
    ]);

    AttendanceRecord::create([
        'class_session_id' => $sessionWithRecords->id,
        'enrollment_detail_id' => $detail->id,
        'status' => AttendanceStatus::Present,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.attendance.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('pending_sessions.data', 0)
        );
});

test('pending sessions include enriched section data (subject, cohort, career, teacher)', function () {
    ['admin' => $admin, 'section' => $section] = adminAttendanceContext();

    ClassSession::factory()->forSection($section)->create([
        'status' => ClassSessionStatus::Scheduled,
        'held_at' => now()->subDay()->format('Y-m-d'),
        'topic' => 'Clase de prueba',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.attendance.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('pending_sessions.data', 1)
            ->has('pending_sessions.data.0', fn ($s) => $s
                ->has('subject')
                ->has('cohort')
                ->has('career')
                ->has('teacher_name')
                ->has('career_color')
                ->where('topic', 'Clase de prueba')
                ->etc()
            )
        );
});

// ---------------------------------------------------------------------------
// sectionIndex — GET /admin/sections/{section}/attendance
// ---------------------------------------------------------------------------

test('unauthenticated users are redirected from admin section attendance', function () {
    $section = Section::factory()->create();

    $this->get(route('admin.sections.attendance.index', $section))->assertRedirect(route('login'));
});

test('non-admin cannot access admin section attendance', function () {
    $user = User::factory()->create();
    $section = Section::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.sections.attendance.index', $section))
        ->assertForbidden();
});

test('admin can view section attendance for any section', function () {
    ['admin' => $admin, 'section' => $section] = adminAttendanceContext();

    ClassSession::factory()->forSection($section)->count(2)->create();

    $this->actingAs($admin)
        ->get(route('admin.sections.attendance.index', $section))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/attendance/SectionIndex')
            ->has('section')
            ->has('sessions')
        );
});

// ---------------------------------------------------------------------------
// storeSession — POST /admin/sections/{section}/attendance/sessions
// ---------------------------------------------------------------------------

test('admin can create a class session for any section', function () {
    ['admin' => $admin, 'section' => $section] = adminAttendanceContext();

    $this->actingAs($admin)
        ->post(route('admin.sections.attendance.sessions.store', $section), [
            'type' => 'regular',
            'topic' => 'Sesión creada por admin',
            'linked_session_id' => null,
            'held_at' => null,
        ])
        ->assertRedirect(route('admin.sections.attendance.index', $section));

    expect(ClassSession::where('section_id', $section->id)->where('type', ClassSessionType::Regular)->exists())
        ->toBeTrue();
});

test('non-admin cannot create sessions via admin route', function () {
    $user = User::factory()->create();
    $section = Section::factory()->create();

    $this->actingAs($user)
        ->post(route('admin.sections.attendance.sessions.store', $section), [
            'type' => 'regular',
        ])
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// sheet — GET /admin/sections/{section}/attendance/sessions/{classSession}
// ---------------------------------------------------------------------------

test('admin can view the attendance sheet for any session', function () {
    ['admin' => $admin, 'section' => $section] = adminAttendanceContext();

    $session = ClassSession::factory()->forSection($section)->create();
    adminConfirmedDetail($section);

    $this->actingAs($admin)
        ->get(route('admin.sections.attendance.sheet', [$section, $session]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/attendance/Sheet')
            ->has('sheet')
        );
});

// ---------------------------------------------------------------------------
// upsertAttendance — PUT /admin/sections/{section}/attendance/sessions/{classSession}
// ---------------------------------------------------------------------------

test('admin can save attendance and professor_present is always false', function () {
    ['admin' => $admin, 'section' => $section] = adminAttendanceContext();

    $session = ClassSession::factory()->forSection($section)->create();
    $detail = adminConfirmedDetail($section);

    // Admin does NOT send professor_present — it should default to false
    $this->actingAs($admin)
        ->put(route('admin.sections.attendance.upsert', [$section, $session]), [
            'marks' => [(string) $detail->id => 'present'],
        ])
        ->assertRedirect(route('admin.sections.attendance.sheet', [$section, $session]));

    expect(AttendanceRecord::where('class_session_id', $session->id)
        ->where('enrollment_detail_id', $detail->id)
        ->where('status', AttendanceStatus::Present)
        ->exists()
    )->toBeTrue();

    $session->refresh();
    expect($session->status)->toBe(ClassSessionStatus::Held);
    expect($session->professor_present)->toBeFalse();
});

test('admin cannot override professor_present to true via request body', function () {
    ['admin' => $admin, 'section' => $section] = adminAttendanceContext();

    $session = ClassSession::factory()->forSection($section)->create();
    $detail = adminConfirmedDetail($section);

    // Even if admin sends professor_present=true it should be forced to false
    $this->actingAs($admin)
        ->put(route('admin.sections.attendance.upsert', [$section, $session]), [
            'marks' => [(string) $detail->id => 'present'],
            'professor_present' => true,
        ])
        ->assertRedirect();

    $session->refresh();
    expect($session->professor_present)->toBeFalse();
});

test('upsertAttendance validates marks is required for admin', function () {
    ['admin' => $admin, 'section' => $section] = adminAttendanceContext();
    $session = ClassSession::factory()->forSection($section)->create();

    $this->actingAs($admin)
        ->put(route('admin.sections.attendance.upsert', [$section, $session]), [])
        ->assertSessionHasErrors('marks');
});

test('non-admin cannot save attendance via admin route', function () {
    $user = User::factory()->create();
    $section = Section::factory()->create();
    $session = ClassSession::factory()->forSection($section)->create();

    $this->actingAs($user)
        ->put(route('admin.sections.attendance.upsert', [$section, $session]), [
            'marks' => [],
        ])
        ->assertForbidden();
});
