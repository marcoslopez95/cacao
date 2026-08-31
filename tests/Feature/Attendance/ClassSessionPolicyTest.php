<?php

use App\Enums\DayOfWeek;
use App\Models\ClassSession;
use App\Models\Professor;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach ([
        'class_sessions.view',
        'class_sessions.create',
        'class_sessions.update',
        'class_sessions.take_attendance',
    ] as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
    }
});

afterEach(function () {
    Carbon::setTestNow();
});

/**
 * Creates a real Schedule for the section and freezes "now" to a moment inside it
 * (feature 20-attendance-scheduling-and-recovery: Regular sessions require create/takeAttendance
 * to happen within a real Schedule window). Anchored on 2024-01-01, a known Monday.
 */
function policyTestWithinScheduleWindow(Section $section): void
{
    Schedule::factory()->create([
        'section_id' => $section->id,
        'day_of_week' => DayOfWeek::Monday,
        'start_time' => '08:00:00',
        'end_time' => '09:00:00',
    ]);

    Carbon::setTestNow(Carbon::parse('2024-01-01 08:30:00'));
}

// ---------------------------------------------------------------------------
// viewAny — [ClassSession::class, $section]
// ---------------------------------------------------------------------------

test('viewAny — professor can view sessions in their own section', function () {
    $professor = Professor::factory()->create();
    $section = Section::factory()->create(['main_teacher_id' => $professor->id]);

    expect($professor->user->can('viewAny', [ClassSession::class, $section]))->toBeTrue();
});

test('viewAny — professor cannot view sessions in another section', function () {
    $professor = Professor::factory()->create();
    $otherProfessor = Professor::factory()->create();
    $section = Section::factory()->create(['main_teacher_id' => $otherProfessor->id]);

    expect($professor->user->can('viewAny', [ClassSession::class, $section]))->toBeFalse();
});

test('viewAny — non-professor user is denied', function () {
    $user = User::factory()->create();
    $section = Section::factory()->create(['main_teacher_id' => null]);

    expect($user->can('viewAny', [ClassSession::class, $section]))->toBeFalse();
});

// ---------------------------------------------------------------------------
// create — [ClassSession::class, $section]
// ---------------------------------------------------------------------------

test('create — professor can create sessions in their own section', function () {
    $professor = Professor::factory()->create();
    $section = Section::factory()->create(['main_teacher_id' => $professor->id]);
    policyTestWithinScheduleWindow($section);

    expect($professor->user->can('create', [ClassSession::class, $section]))->toBeTrue();
});

test('create — professor cannot create sessions in another section', function () {
    $professor = Professor::factory()->create();
    $otherProfessor = Professor::factory()->create();
    $section = Section::factory()->create(['main_teacher_id' => $otherProfessor->id]);

    expect($professor->user->can('create', [ClassSession::class, $section]))->toBeFalse();
});

test('create — non-professor user is denied', function () {
    $user = User::factory()->create();
    $section = Section::factory()->create(['main_teacher_id' => null]);

    expect($user->can('create', [ClassSession::class, $section]))->toBeFalse();
});

// ---------------------------------------------------------------------------
// update — $classSession
// ---------------------------------------------------------------------------

test('update — professor can update sessions in their own section', function () {
    $professor = Professor::factory()->create();
    $section = Section::factory()->create(['main_teacher_id' => $professor->id]);
    $classSession = ClassSession::factory()->forSection($section)->create();

    expect($professor->user->can('update', $classSession))->toBeTrue();
});

test('update — professor cannot update sessions in another section', function () {
    $professor = Professor::factory()->create();
    $otherProfessor = Professor::factory()->create();
    $section = Section::factory()->create(['main_teacher_id' => $otherProfessor->id]);
    $classSession = ClassSession::factory()->forSection($section)->create();

    expect($professor->user->can('update', $classSession))->toBeFalse();
});

test('update — non-professor user is denied', function () {
    $section = Section::factory()->create(['main_teacher_id' => null]);
    $classSession = ClassSession::factory()->forSection($section)->create();
    $user = User::factory()->create();

    expect($user->can('update', $classSession))->toBeFalse();
});

// ---------------------------------------------------------------------------
// takeAttendance — $classSession
// ---------------------------------------------------------------------------

test('takeAttendance — professor can take attendance in their own section', function () {
    $professor = Professor::factory()->create();
    $section = Section::factory()->create(['main_teacher_id' => $professor->id]);
    $classSession = ClassSession::factory()->forSection($section)->create();
    policyTestWithinScheduleWindow($section);

    expect($professor->user->can('takeAttendance', $classSession))->toBeTrue();
});

test('takeAttendance — professor cannot take attendance in another section', function () {
    $professor = Professor::factory()->create();
    $otherProfessor = Professor::factory()->create();
    $section = Section::factory()->create(['main_teacher_id' => $otherProfessor->id]);
    $classSession = ClassSession::factory()->forSection($section)->create();

    expect($professor->user->can('takeAttendance', $classSession))->toBeFalse();
});

test('takeAttendance — non-professor user is denied', function () {
    $section = Section::factory()->create(['main_teacher_id' => null]);
    $classSession = ClassSession::factory()->forSection($section)->create();
    $user = User::factory()->create();

    expect($user->can('takeAttendance', $classSession))->toBeFalse();
});
