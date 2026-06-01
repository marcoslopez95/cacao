<?php

use App\Actions\Attendance\CreateAdvanceSessionAction;
use App\Actions\Attendance\CreateClassSessionAction;
use App\Actions\Attendance\CreateMakeupSessionAction;
use App\Actions\Attendance\TakeAttendanceAction;
use App\Enums\AttendanceStatus;
use App\Enums\ClassSessionStatus;
use App\Enums\ClassSessionType;
use App\Http\Wrappers\Attendance\AttendanceSheetWrapper;
use App\Http\Wrappers\Attendance\ClassSessionWrapper;
use App\Models\AttendanceRecord;
use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\EnrollmentDetail;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// CreateClassSessionAction
// ---------------------------------------------------------------------------

test('CreateClassSessionAction creates a ClassSession with correct data', function () {
    $section = Section::factory()->create();

    $wrapper = new ClassSessionWrapper([
        'section_id' => $section->id,
        'type' => ClassSessionType::Regular->value,
        'topic' => 'Introducción al cálculo diferencial',
    ]);

    $action = new CreateClassSessionAction;
    $session = $action->handle($wrapper);

    expect($session)->toBeInstanceOf(ClassSession::class)
        ->and($session->exists)->toBeTrue()
        ->and($session->section_id)->toBe($section->id)
        ->and($session->type)->toBe(ClassSessionType::Regular)
        ->and($session->status)->toBe(ClassSessionStatus::Scheduled)
        ->and($session->topic)->toBe('Introducción al cálculo diferencial')
        ->and($session->professor_present)->toBeTrue();
});

test('CreateClassSessionAction always sets status to Scheduled regardless of wrapper status', function () {
    $section = Section::factory()->create();

    $wrapper = new ClassSessionWrapper([
        'section_id' => $section->id,
        'type' => ClassSessionType::Makeup->value,
        'status' => ClassSessionStatus::Held->value,  // intentionally wrong
    ]);

    $action = new CreateClassSessionAction;
    $session = $action->handle($wrapper);

    expect($session->status)->toBe(ClassSessionStatus::Scheduled);
});

test('CreateClassSessionAction stores nullable topic and linked_session_id', function () {
    $section = Section::factory()->create();
    $linked = ClassSession::factory()->forSection($section)->create();

    $wrapper = new ClassSessionWrapper([
        'section_id' => $section->id,
        'type' => ClassSessionType::Advance->value,
        'linked_session_id' => $linked->id,
        'topic' => null,
    ]);

    $action = new CreateClassSessionAction;
    $session = $action->handle($wrapper);

    expect($session->linked_session_id)->toBe($linked->id)
        ->and($session->topic)->toBeNull();
});

// ---------------------------------------------------------------------------
// TakeAttendanceAction
// ---------------------------------------------------------------------------

test('TakeAttendanceAction creates AttendanceRecords and marks session as held', function () {
    $section = Section::factory()->create();
    $classSession = ClassSession::factory()->forSection($section)->create([
        'status' => ClassSessionStatus::Scheduled,
    ]);

    $students = Student::factory()->count(3)->create();
    $enrollment = Enrollment::factory()->create();
    $details = $students->map(fn ($student) => EnrollmentDetail::factory()->create([
        'enrollment_id' => $enrollment->id,
        'section_id' => $section->id,
    ]));

    $marks = $details->mapWithKeys(fn ($detail, $i) => [
        $detail->id => $i === 1 ? 'absent' : 'present',
    ])->all();

    $wrapper = new AttendanceSheetWrapper([
        'class_session_id' => $classSession->id,
        'marks' => $marks,
        'professor_present' => true,
    ]);

    $action = new TakeAttendanceAction;
    $action->handle($wrapper);

    // All 3 attendance records created
    expect(AttendanceRecord::where('class_session_id', $classSession->id)->count())->toBe(3);

    // Correct statuses
    foreach ($details as $i => $detail) {
        $record = AttendanceRecord::where('class_session_id', $classSession->id)
            ->where('enrollment_detail_id', $detail->id)
            ->first();

        expect($record)->not->toBeNull();
        $expectedStatus = $i === 1 ? AttendanceStatus::Absent : AttendanceStatus::Present;
        expect($record->status)->toBe($expectedStatus);
    }

    // Session updated
    $classSession->refresh();
    expect($classSession->status)->toBe(ClassSessionStatus::Held)
        ->and($classSession->held_at->format('Y-m-d'))->toBe(today()->format('Y-m-d'))
        ->and($classSession->professor_present)->toBeTrue();
});

test('TakeAttendanceAction does upsert — updating existing records correctly', function () {
    $section = Section::factory()->create();
    $classSession = ClassSession::factory()->forSection($section)->create();
    $enrollment = Enrollment::factory()->create();
    $detail = EnrollmentDetail::factory()->create([
        'enrollment_id' => $enrollment->id,
        'section_id' => $section->id,
    ]);

    // Pre-existing record with 'present'
    AttendanceRecord::create([
        'class_session_id' => $classSession->id,
        'enrollment_detail_id' => $detail->id,
        'status' => AttendanceStatus::Present,
    ]);

    // Re-submit with 'absent'
    $wrapper = new AttendanceSheetWrapper([
        'class_session_id' => $classSession->id,
        'marks' => [$detail->id => 'absent'],
        'professor_present' => false,
    ]);

    $action = new TakeAttendanceAction;
    $action->handle($wrapper);

    // Still only 1 record (upsert, not duplicate)
    expect(AttendanceRecord::where('class_session_id', $classSession->id)->count())->toBe(1);

    $record = AttendanceRecord::where('class_session_id', $classSession->id)
        ->where('enrollment_detail_id', $detail->id)
        ->first();

    expect($record->status)->toBe(AttendanceStatus::Absent);

    // professor_present updated
    $classSession->refresh();
    expect($classSession->professor_present)->toBeFalse();
});

test('TakeAttendanceAction with no marks still updates session to held', function () {
    $section = Section::factory()->create();
    $classSession = ClassSession::factory()->forSection($section)->create([
        'status' => ClassSessionStatus::Scheduled,
    ]);

    $wrapper = new AttendanceSheetWrapper([
        'class_session_id' => $classSession->id,
        'marks' => [],
        'professor_present' => true,
    ]);

    $action = new TakeAttendanceAction;
    $action->handle($wrapper);

    expect(AttendanceRecord::where('class_session_id', $classSession->id)->count())->toBe(0);

    $classSession->refresh();
    expect($classSession->status)->toBe(ClassSessionStatus::Held);
});

// ---------------------------------------------------------------------------
// CreateMakeupSessionAction
// ---------------------------------------------------------------------------

test('CreateMakeupSessionAction creates a Makeup session and marks linked as Recovered', function () {
    $section = Section::factory()->create();
    $cancelledSession = ClassSession::factory()->forSection($section)->create([
        'type' => ClassSessionType::Regular,
        'status' => ClassSessionStatus::Cancelled,
    ]);

    $wrapper = new ClassSessionWrapper([
        'section_id' => $section->id,
        'type' => ClassSessionType::Makeup->value,
        'linked_session_id' => $cancelledSession->id,
        'topic' => 'Sesión de recuperación',
    ]);

    $action = new CreateMakeupSessionAction;
    $makeupSession = $action->handle($wrapper);

    expect($makeupSession)->toBeInstanceOf(ClassSession::class)
        ->and($makeupSession->exists)->toBeTrue()
        ->and($makeupSession->type)->toBe(ClassSessionType::Makeup)
        ->and($makeupSession->status)->toBe(ClassSessionStatus::Scheduled)
        ->and($makeupSession->linked_session_id)->toBe($cancelledSession->id)
        ->and($makeupSession->section_id)->toBe($section->id);

    $cancelledSession->refresh();
    expect($cancelledSession->status)->toBe(ClassSessionStatus::Recovered)
        ->and($cancelledSession->linked_session_id)->toBe($makeupSession->id);
});

test('CreateMakeupSessionAction throws InvalidArgumentException when linked_session_id is null', function () {
    $section = Section::factory()->create();

    $wrapper = new ClassSessionWrapper([
        'section_id' => $section->id,
        'type' => ClassSessionType::Makeup->value,
        'linked_session_id' => null,
    ]);

    $action = new CreateMakeupSessionAction;

    expect(fn () => $action->handle($wrapper))->toThrow(InvalidArgumentException::class);
});

// ---------------------------------------------------------------------------
// CreateAdvanceSessionAction
// ---------------------------------------------------------------------------

test('CreateAdvanceSessionAction creates an Advance session and marks linked as Advanced', function () {
    $section = Section::factory()->create();
    $futureSession = ClassSession::factory()->forSection($section)->create([
        'type' => ClassSessionType::Regular,
        'status' => ClassSessionStatus::Scheduled,
    ]);

    $wrapper = new ClassSessionWrapper([
        'section_id' => $section->id,
        'type' => ClassSessionType::Advance->value,
        'linked_session_id' => $futureSession->id,
        'topic' => 'Clase adelantada',
    ]);

    $action = new CreateAdvanceSessionAction;
    $advanceSession = $action->handle($wrapper);

    expect($advanceSession)->toBeInstanceOf(ClassSession::class)
        ->and($advanceSession->exists)->toBeTrue()
        ->and($advanceSession->type)->toBe(ClassSessionType::Advance)
        ->and($advanceSession->status)->toBe(ClassSessionStatus::Scheduled)
        ->and($advanceSession->linked_session_id)->toBe($futureSession->id)
        ->and($advanceSession->section_id)->toBe($section->id);

    $futureSession->refresh();
    expect($futureSession->status)->toBe(ClassSessionStatus::Advanced)
        ->and($futureSession->linked_session_id)->toBe($advanceSession->id);
});

test('CreateAdvanceSessionAction throws InvalidArgumentException when linked_session_id is null', function () {
    $section = Section::factory()->create();

    $wrapper = new ClassSessionWrapper([
        'section_id' => $section->id,
        'type' => ClassSessionType::Advance->value,
        'linked_session_id' => null,
    ]);

    $action = new CreateAdvanceSessionAction;

    expect(fn () => $action->handle($wrapper))->toThrow(InvalidArgumentException::class);
});

// ---------------------------------------------------------------------------
// TakeAttendanceAction — advance session copies records to linked session
// ---------------------------------------------------------------------------

test('TakeAttendanceAction copies AttendanceRecords to linked session when type is Advance', function () {
    $section = Section::factory()->create();

    // The future session (linked, already Advanced)
    $linkedSession = ClassSession::factory()->forSection($section)->create([
        'type' => ClassSessionType::Regular,
        'status' => ClassSessionStatus::Advanced,
    ]);

    // The advance session that points at the future session
    $advanceSession = ClassSession::factory()->forSection($section)->create([
        'type' => ClassSessionType::Advance,
        'status' => ClassSessionStatus::Scheduled,
        'linked_session_id' => $linkedSession->id,
    ]);

    $enrollment = Enrollment::factory()->create();
    $details = EnrollmentDetail::factory()->count(2)->create([
        'enrollment_id' => $enrollment->id,
        'section_id' => $section->id,
    ]);

    $marks = [
        $details[0]->id => 'present',
        $details[1]->id => 'absent',
    ];

    $wrapper = new AttendanceSheetWrapper([
        'class_session_id' => $advanceSession->id,
        'marks' => $marks,
        'professor_present' => true,
    ]);

    $action = new TakeAttendanceAction;
    $action->handle($wrapper);

    // Records exist on advance session
    expect(AttendanceRecord::where('class_session_id', $advanceSession->id)->count())->toBe(2);

    // Records also copied to linked session
    expect(AttendanceRecord::where('class_session_id', $linkedSession->id)->count())->toBe(2);

    // Statuses match
    $linkedPresent = AttendanceRecord::where('class_session_id', $linkedSession->id)
        ->where('enrollment_detail_id', $details[0]->id)
        ->first();

    $linkedAbsent = AttendanceRecord::where('class_session_id', $linkedSession->id)
        ->where('enrollment_detail_id', $details[1]->id)
        ->first();

    expect($linkedPresent->status)->toBe(AttendanceStatus::Present)
        ->and($linkedAbsent->status)->toBe(AttendanceStatus::Absent);
});

test('TakeAttendanceAction does NOT copy records when session type is Regular', function () {
    $section = Section::factory()->create();

    $regularSession = ClassSession::factory()->forSection($section)->create([
        'type' => ClassSessionType::Regular,
        'status' => ClassSessionStatus::Scheduled,
        'linked_session_id' => null,
    ]);

    $enrollment = Enrollment::factory()->create();
    $detail = EnrollmentDetail::factory()->create([
        'enrollment_id' => $enrollment->id,
        'section_id' => $section->id,
    ]);

    $wrapper = new AttendanceSheetWrapper([
        'class_session_id' => $regularSession->id,
        'marks' => [$detail->id => 'present'],
        'professor_present' => true,
    ]);

    (new TakeAttendanceAction)->handle($wrapper);

    // Only the one record on the session itself
    expect(AttendanceRecord::count())->toBe(1);
});
