<?php

use App\Enums\DayOfWeek;
use App\Enums\PeriodStatus;
use App\Models\Classroom;
use App\Models\Period;
use App\Models\Professor;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\Subject;
use App\Services\Scheduling\ScheduleConflictService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function makeSection(Period $period): Section
{
    return Section::factory()->university()->create(['period_id' => $period->id]);
}

function activePeriod(): Period
{
    return Period::factory()->semester()->create([
        'status'     => PeriodStatus::Active,
        'start_date' => '2026-01-01',
        'end_date'   => '2026-06-30',
    ]);
}

function closedPeriod(): Period
{
    return Period::factory()->semester()->create([
        'status'     => PeriodStatus::Closed,
        'start_date' => '2025-01-01',
        'end_date'   => '2025-06-30',
    ]);
}

// ---------------------------------------------------------------------------
// classroomConflict
// ---------------------------------------------------------------------------

test('classroomConflict detects exact overlap', function () {
    $service   = new ScheduleConflictService();
    $period    = activePeriod();
    $section   = makeSection($period);
    $classroom = Classroom::factory()->create();

    $existing = Schedule::factory()->create([
        'section_id'   => $section->id,
        'classroom_id' => $classroom->id,
        'day_of_week'  => DayOfWeek::Monday->value,
        'start_time'   => '08:00:00',
        'end_time'     => '09:00:00',
        'valid_from'   => '2026-01-15',
        'valid_until'  => null,
    ]);

    $candidate = Schedule::factory()->make([
        'section_id'   => $section->id,
        'classroom_id' => $classroom->id,
        'day_of_week'  => DayOfWeek::Monday->value,
        'start_time'   => '08:00:00',
        'end_time'     => '09:00:00',
        'valid_from'   => '2026-01-15',
        'valid_until'  => null,
    ]);
    $candidate->setRelation('section', $section);

    expect($service->classroomConflict($candidate)?->id)->toBe($existing->id);
});

test('classroomConflict detects partial overlap', function () {
    $service   = new ScheduleConflictService();
    $period    = activePeriod();
    $section   = makeSection($period);
    $classroom = Classroom::factory()->create();

    $existing = Schedule::factory()->create([
        'section_id'   => $section->id,
        'classroom_id' => $classroom->id,
        'day_of_week'  => DayOfWeek::Monday->value,
        'start_time'   => '08:00:00',
        'end_time'     => '09:00:00',
        'valid_from'   => '2026-01-15',
        'valid_until'  => null,
    ]);

    $candidate = Schedule::factory()->make([
        'section_id'   => $section->id,
        'classroom_id' => $classroom->id,
        'day_of_week'  => DayOfWeek::Monday->value,
        'start_time'   => '08:30:00',
        'end_time'     => '09:30:00',
        'valid_from'   => '2026-01-15',
        'valid_until'  => null,
    ]);
    $candidate->setRelation('section', $section);

    expect($service->classroomConflict($candidate)?->id)->toBe($existing->id);
});

test('classroomConflict does not flag adjacent slots as conflict', function () {
    $service   = new ScheduleConflictService();
    $period    = activePeriod();
    $section   = makeSection($period);
    $classroom = Classroom::factory()->create();

    Schedule::factory()->create([
        'section_id'   => $section->id,
        'classroom_id' => $classroom->id,
        'day_of_week'  => DayOfWeek::Monday->value,
        'start_time'   => '08:00:00',
        'end_time'     => '09:00:00',
        'valid_from'   => '2026-01-15',
        'valid_until'  => null,
    ]);

    $candidate = Schedule::factory()->make([
        'section_id'   => $section->id,
        'classroom_id' => $classroom->id,
        'day_of_week'  => DayOfWeek::Monday->value,
        'start_time'   => '09:00:00',
        'end_time'     => '10:00:00',
        'valid_from'   => '2026-01-15',
        'valid_until'  => null,
    ]);
    $candidate->setRelation('section', $section);

    expect($service->classroomConflict($candidate))->toBeNull();
});

test('classroomConflict ignores slots in closed periods', function () {
    $service   = new ScheduleConflictService();
    $closed    = closedPeriod();
    $section   = makeSection($closed);
    $classroom = Classroom::factory()->create();

    Schedule::factory()->create([
        'section_id'   => $section->id,
        'classroom_id' => $classroom->id,
        'day_of_week'  => DayOfWeek::Monday->value,
        'start_time'   => '08:00:00',
        'end_time'     => '09:00:00',
        'valid_from'   => '2025-01-15',
        'valid_until'  => null,
    ]);

    $activePeriod  = activePeriod();
    $activeSection = makeSection($activePeriod);

    $candidate = Schedule::factory()->make([
        'classroom_id' => $classroom->id,
        'day_of_week'  => DayOfWeek::Monday->value,
        'start_time'   => '08:00:00',
        'end_time'     => '09:00:00',
        'valid_from'   => '2026-01-15',
        'valid_until'  => null,
    ]);
    $candidate->setRelation('section', $activeSection);

    expect($service->classroomConflict($candidate))->toBeNull();
});

test('classroomConflict does not conflict with itself on update', function () {
    $service   = new ScheduleConflictService();
    $period    = activePeriod();
    $section   = makeSection($period);
    $classroom = Classroom::factory()->create();

    $schedule = Schedule::factory()->create([
        'section_id'   => $section->id,
        'classroom_id' => $classroom->id,
        'day_of_week'  => DayOfWeek::Monday->value,
        'start_time'   => '08:00:00',
        'end_time'     => '09:00:00',
        'valid_from'   => '2026-01-15',
        'valid_until'  => null,
    ]);
    $schedule->setRelation('section', $section);

    expect($service->classroomConflict($schedule))->toBeNull();
});

// ---------------------------------------------------------------------------
// professorConflict
// ---------------------------------------------------------------------------

test('professorConflict detects same professor overlapping in different sections', function () {
    $service   = new ScheduleConflictService();
    $period    = activePeriod();
    $section1  = makeSection($period);
    $section2  = makeSection($period);
    $professor = Professor::factory()->create();

    $existing = Schedule::factory()->create([
        'section_id'   => $section1->id,
        'professor_id' => $professor->id,
        'day_of_week'  => DayOfWeek::Tuesday->value,
        'start_time'   => '10:00:00',
        'end_time'     => '11:00:00',
        'valid_from'   => '2026-01-15',
        'valid_until'  => null,
    ]);

    $candidate = Schedule::factory()->make([
        'section_id'   => $section2->id,
        'professor_id' => $professor->id,
        'day_of_week'  => DayOfWeek::Tuesday->value,
        'start_time'   => '10:30:00',
        'end_time'     => '11:30:00',
        'valid_from'   => '2026-01-15',
        'valid_until'  => null,
    ]);
    $candidate->setRelation('section', $section2);

    expect($service->professorConflict($candidate)?->id)->toBe($existing->id);
});

test('professorConflict does not flag different days', function () {
    $service   = new ScheduleConflictService();
    $period    = activePeriod();
    $section1  = makeSection($period);
    $section2  = makeSection($period);
    $professor = Professor::factory()->create();

    Schedule::factory()->create([
        'section_id'   => $section1->id,
        'professor_id' => $professor->id,
        'day_of_week'  => DayOfWeek::Monday->value,
        'start_time'   => '10:00:00',
        'end_time'     => '11:00:00',
        'valid_from'   => '2026-01-15',
        'valid_until'  => null,
    ]);

    $candidate = Schedule::factory()->make([
        'section_id'   => $section2->id,
        'professor_id' => $professor->id,
        'day_of_week'  => DayOfWeek::Tuesday->value,
        'start_time'   => '10:00:00',
        'end_time'     => '11:00:00',
        'valid_from'   => '2026-01-15',
        'valid_until'  => null,
    ]);
    $candidate->setRelation('section', $section2);

    expect($service->professorConflict($candidate))->toBeNull();
});

test('professorConflict ignores slots in closed periods', function () {
    $service   = new ScheduleConflictService();
    $closed    = closedPeriod();
    $section1  = makeSection($closed);
    $professor = Professor::factory()->create();

    Schedule::factory()->create([
        'section_id'   => $section1->id,
        'professor_id' => $professor->id,
        'day_of_week'  => DayOfWeek::Monday->value,
        'start_time'   => '10:00:00',
        'end_time'     => '11:00:00',
        'valid_from'   => '2025-01-15',
        'valid_until'  => null,
    ]);

    $active   = activePeriod();
    $section2 = makeSection($active);

    $candidate = Schedule::factory()->make([
        'section_id'   => $section2->id,
        'professor_id' => $professor->id,
        'day_of_week'  => DayOfWeek::Monday->value,
        'start_time'   => '10:00:00',
        'end_time'     => '11:00:00',
        'valid_from'   => '2026-01-15',
        'valid_until'  => null,
    ]);
    $candidate->setRelation('section', $section2);

    expect($service->professorConflict($candidate))->toBeNull();
});

// ---------------------------------------------------------------------------
// professorWeeklyHoursExceeded
// ---------------------------------------------------------------------------

test('professorWeeklyHoursExceeded returns true when limit would be exceeded', function () {
    $service   = new ScheduleConflictService();
    $period    = activePeriod();
    $section   = makeSection($period);
    $professor = Professor::factory()->create(['weekly_hour_limit' => 4]);

    // Existing: 2 h + 1.5 h = 3.5 h already assigned
    Schedule::factory()->create([
        'section_id'   => $section->id,
        'professor_id' => $professor->id,
        'start_time'   => '08:00:00',
        'end_time'     => '10:00:00',
        'valid_from'   => '2026-01-15',
        'valid_until'  => null,
    ]);
    Schedule::factory()->create([
        'section_id'   => $section->id,
        'professor_id' => $professor->id,
        'start_time'   => '11:00:00',
        'end_time'     => '12:30:00',
        'valid_from'   => '2026-01-15',
        'valid_until'  => null,
    ]);

    // New slot: 1 h → total 4.5 h > limit 4
    $candidate = Schedule::factory()->make([
        'professor_id' => $professor->id,
        'start_time'   => '14:00:00',
        'end_time'     => '15:00:00',
        'valid_from'   => '2026-01-15',
        'valid_until'  => null,
    ]);
    $candidate->setRelation('professor', $professor);

    expect($service->professorWeeklyHoursExceeded($candidate))->toBeTrue();
});

test('professorWeeklyHoursExceeded returns false when within limit', function () {
    $service   = new ScheduleConflictService();
    $period    = activePeriod();
    $section   = makeSection($period);
    $professor = Professor::factory()->create(['weekly_hour_limit' => 10]);

    Schedule::factory()->create([
        'section_id'   => $section->id,
        'professor_id' => $professor->id,
        'start_time'   => '08:00:00',
        'end_time'     => '10:00:00',
        'valid_from'   => '2026-01-15',
        'valid_until'  => null,
    ]);

    // New slot: 1 h → total 3 h < limit 10
    $candidate = Schedule::factory()->make([
        'professor_id' => $professor->id,
        'start_time'   => '11:00:00',
        'end_time'     => '12:00:00',
        'valid_from'   => '2026-01-15',
        'valid_until'  => null,
    ]);
    $candidate->setRelation('professor', $professor);

    expect($service->professorWeeklyHoursExceeded($candidate))->toBeFalse();
});

test('professorWeeklyHoursExceeded on update excludes own slot from count', function () {
    $service   = new ScheduleConflictService();
    $period    = activePeriod();
    $section   = makeSection($period);
    $professor = Professor::factory()->create(['weekly_hour_limit' => 3]);

    // One 3-h slot already = exactly at limit
    $existing = Schedule::factory()->create([
        'section_id'   => $section->id,
        'professor_id' => $professor->id,
        'start_time'   => '08:00:00',
        'end_time'     => '11:00:00',
        'valid_from'   => '2026-01-15',
        'valid_until'  => null,
    ]);
    $existing->setRelation('professor', $professor);

    // Updating that same slot (extending 30 min → 3.5 h, still > limit if counted twice)
    // Since we exclude its own hours, only 0 h existing + 3.5 h new = 3.5 h > 3 → true
    $existing->end_time = '11:30:00';

    expect($service->professorWeeklyHoursExceeded($existing))->toBeTrue();
});

// ---------------------------------------------------------------------------
// professorCurrentWeeklyHours
// ---------------------------------------------------------------------------

test('professorCurrentWeeklyHours sums multiple slots correctly', function () {
    $service   = new ScheduleConflictService();
    $period    = activePeriod();
    $section   = makeSection($period);
    $professor = Professor::factory()->create();

    Schedule::factory()->create([
        'section_id'   => $section->id,
        'professor_id' => $professor->id,
        'start_time'   => '08:00:00',
        'end_time'     => '09:30:00', // 1.5 h
        'valid_from'   => '2026-01-15',
        'valid_until'  => null,
    ]);
    Schedule::factory()->create([
        'section_id'   => $section->id,
        'professor_id' => $professor->id,
        'start_time'   => '10:00:00',
        'end_time'     => '12:00:00', // 2 h
        'valid_from'   => '2026-01-15',
        'valid_until'  => null,
    ]);

    expect($service->professorCurrentWeeklyHours($professor))->toBe(3.5);
});

test('professorCurrentWeeklyHours ignores closed period slots', function () {
    $service   = new ScheduleConflictService();
    $closed    = closedPeriod();
    $section   = makeSection($closed);
    $professor = Professor::factory()->create();

    Schedule::factory()->create([
        'section_id'   => $section->id,
        'professor_id' => $professor->id,
        'start_time'   => '08:00:00',
        'end_time'     => '10:00:00',
        'valid_from'   => '2025-01-15',
        'valid_until'  => null,
    ]);

    expect($service->professorCurrentWeeklyHours($professor))->toBe(0.0);
});

test('professorCurrentWeeklyHours excludes slot by id when passed', function () {
    $service   = new ScheduleConflictService();
    $period    = activePeriod();
    $section   = makeSection($period);
    $professor = Professor::factory()->create();

    $slot = Schedule::factory()->create([
        'section_id'   => $section->id,
        'professor_id' => $professor->id,
        'start_time'   => '08:00:00',
        'end_time'     => '10:00:00', // 2 h
        'valid_from'   => '2026-01-15',
        'valid_until'  => null,
    ]);
    Schedule::factory()->create([
        'section_id'   => $section->id,
        'professor_id' => $professor->id,
        'start_time'   => '11:00:00',
        'end_time'     => '12:00:00', // 1 h
        'valid_from'   => '2026-01-15',
        'valid_until'  => null,
    ]);

    // Total is 3h but excluding the 2h slot → should return 1h
    expect($service->professorCurrentWeeklyHours($professor, $slot->id))->toBe(1.0);
});
