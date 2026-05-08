<?php

use App\Enums\DayOfWeek;
use App\Enums\ScheduleSessionType;
use App\Models\Classroom;
use App\Models\Period;
use App\Models\Professor;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach (['schedules.view', 'schedules.create', 'schedules.update', 'schedules.delete'] as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
    }
});

function userWithSchedulePerm(string $permission): User
{
    $user = User::factory()->create();
    $user->givePermissionTo($permission);
    return $user;
}

test('unauthenticated user is redirected to login', function () {
    $this->get('/scheduling/schedules')->assertRedirect('/login');
});

test('user without permission gets 403', function () {
    $this->actingAs(User::factory()->create())
        ->get('/scheduling/schedules')
        ->assertForbidden();
});

test('admin can list schedules', function () {
    Schedule::factory()->count(3)->create();

    $this->actingAs(userWithSchedulePerm('schedules.view'))
        ->get('/scheduling/schedules')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('scheduling/Schedules/Index', false)
            ->has('schedules', 3)
        );
});

test('admin can filter schedules by section', function () {
    $sectionA = Section::factory()->university()->create();
    $sectionB = Section::factory()->university()->create();
    Schedule::factory()->count(2)->for($sectionA, 'section')->create();
    Schedule::factory()->count(1)->for($sectionB, 'section')->create();

    $this->actingAs(userWithSchedulePerm('schedules.view'))
        ->get("/scheduling/schedules?section_id={$sectionA->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('schedules', 2));
});

test('admin can create a schedule without conflicts', function () {
    $section   = Section::factory()->university()->create();
    $professor = Professor::factory()->create();
    $classroom = Classroom::factory()->create();
    $subject   = $section->subject;

    $data = [
        'section_id'   => $section->id,
        'professor_id' => $professor->id,
        'classroom_id' => $classroom->id,
        'subject_id'   => $subject->id,
        'day_of_week'  => DayOfWeek::Monday->value,
        'start_time'   => '08:00',
        'end_time'     => '08:45',
        'type'         => ScheduleSessionType::Theory->value,
        'valid_from'   => $section->period->start_date->toDateString(),
        'valid_until'  => null,
    ];

    $this->actingAs(userWithSchedulePerm('schedules.create'))
        ->post('/scheduling/schedules', $data)
        ->assertRedirect();

    expect(Schedule::where('section_id', $section->id)->exists())->toBeTrue();
});

test('admin cannot create schedule with occupied classroom', function () {
    $section   = Section::factory()->university()->create();
    $section2  = Section::factory()->university()->create();
    $professor = Professor::factory()->create();
    $classroom = Classroom::factory()->create();

    Schedule::factory()->create([
        'classroom_id' => $classroom->id,
        'day_of_week'  => DayOfWeek::Monday,
        'start_time'   => '08:00',
        'end_time'     => '08:45',
    ]);

    $data = [
        'section_id'   => $section2->id,
        'professor_id' => $professor->id,
        'classroom_id' => $classroom->id,
        'subject_id'   => $section2->subject_id,
        'day_of_week'  => DayOfWeek::Monday->value,
        'start_time'   => '08:00',
        'end_time'     => '08:45',
        'type'         => ScheduleSessionType::Theory->value,
        'valid_from'   => $section2->period->start_date->toDateString(),
        'valid_until'  => null,
    ];

    $this->actingAs(userWithSchedulePerm('schedules.create'))
        ->withHeader('Accept', 'application/json')
        ->post('/scheduling/schedules', $data)
        ->assertStatus(422)
        ->assertJsonValidationErrorFor('classroom_id');
});

test('admin cannot create schedule with occupied professor', function () {
    $section   = Section::factory()->university()->create();
    $section2  = Section::factory()->university()->create();
    $professor = Professor::factory()->create();
    $classroom = Classroom::factory()->create();
    $classroom2 = Classroom::factory()->create();

    Schedule::factory()->create([
        'professor_id' => $professor->id,
        'day_of_week'  => DayOfWeek::Monday,
        'start_time'   => '08:00',
        'end_time'     => '08:45',
    ]);

    $data = [
        'section_id'   => $section2->id,
        'professor_id' => $professor->id,
        'classroom_id' => $classroom2->id,
        'subject_id'   => $section2->subject_id,
        'day_of_week'  => DayOfWeek::Monday->value,
        'start_time'   => '08:00',
        'end_time'     => '08:45',
        'type'         => ScheduleSessionType::Theory->value,
        'valid_from'   => $section2->period->start_date->toDateString(),
        'valid_until'  => null,
    ];

    $this->actingAs(userWithSchedulePerm('schedules.create'))
        ->withHeader('Accept', 'application/json')
        ->post('/scheduling/schedules', $data)
        ->assertStatus(422)
        ->assertJsonValidationErrorFor('professor_id');
});

test('admin cannot create schedule that exceeds professor weekly limit', function () {
    $professor = Professor::factory()->create(['weekly_hour_limit' => 1]);
    $section   = Section::factory()->university()->create();
    $classroom = Classroom::factory()->create();

    // Existing slot fills the 1h limit
    Schedule::factory()->create([
        'professor_id' => $professor->id,
        'day_of_week'  => DayOfWeek::Tuesday,
        'start_time'   => '08:00',
        'end_time'     => '09:00',
    ]);

    $data = [
        'section_id'   => $section->id,
        'professor_id' => $professor->id,
        'classroom_id' => $classroom->id,
        'subject_id'   => $section->subject_id,
        'day_of_week'  => DayOfWeek::Monday->value,
        'start_time'   => '08:00',
        'end_time'     => '08:45',
        'type'         => ScheduleSessionType::Theory->value,
        'valid_from'   => $section->period->start_date->toDateString(),
        'valid_until'  => null,
    ];

    $this->actingAs(userWithSchedulePerm('schedules.create'))
        ->withHeader('Accept', 'application/json')
        ->post('/scheduling/schedules', $data)
        ->assertStatus(422)
        ->assertJsonValidationErrorFor('professor_id');
});

test('updating a schedule does not conflict with itself', function () {
    $section   = Section::factory()->university()->create();
    $professor = Professor::factory()->create();
    $classroom = Classroom::factory()->create();

    $schedule = Schedule::factory()->create([
        'section_id'   => $section->id,
        'professor_id' => $professor->id,
        'classroom_id' => $classroom->id,
        'subject_id'   => $section->subject_id,
        'day_of_week'  => DayOfWeek::Monday,
        'start_time'   => '08:00',
        'end_time'     => '08:45',
        'type'         => ScheduleSessionType::Theory,
        'valid_from'   => $section->period->start_date->toDateString(),
    ]);

    $data = [
        'section_id'   => $section->id,
        'professor_id' => $professor->id,
        'classroom_id' => $classroom->id,
        'subject_id'   => $section->subject_id,
        'day_of_week'  => DayOfWeek::Monday->value,
        'start_time'   => '08:00',
        'end_time'     => '08:45',
        'type'         => ScheduleSessionType::Theory->value,
        'valid_from'   => $section->period->start_date->toDateString(),
        'valid_until'  => null,
    ];

    $this->actingAs(userWithSchedulePerm('schedules.update'))
        ->patch("/scheduling/schedules/{$schedule->id}", $data)
        ->assertRedirect();
});

test('admin can delete a schedule', function () {
    $schedule = Schedule::factory()->create();

    $this->actingAs(userWithSchedulePerm('schedules.delete'))
        ->delete("/scheduling/schedules/{$schedule->id}")
        ->assertRedirect();

    expect(Schedule::find($schedule->id))->toBeNull();
});

test('subject inconsistent with university section is rejected', function () {
    $section      = Section::factory()->university()->create();
    $otherSubject = Subject::factory()->create();
    $professor    = Professor::factory()->create();
    $classroom    = Classroom::factory()->create();

    $data = [
        'section_id'   => $section->id,
        'professor_id' => $professor->id,
        'classroom_id' => $classroom->id,
        'subject_id'   => $otherSubject->id,
        'day_of_week'  => DayOfWeek::Monday->value,
        'start_time'   => '08:00',
        'end_time'     => '08:45',
        'type'         => ScheduleSessionType::Theory->value,
        'valid_from'   => $section->period->start_date->toDateString(),
        'valid_until'  => null,
    ];

    $this->actingAs(userWithSchedulePerm('schedules.create'))
        ->withHeader('Accept', 'application/json')
        ->post('/scheduling/schedules', $data)
        ->assertStatus(422)
        ->assertJsonValidationErrorFor('subject_id');
});

test('valid_from before period start is rejected', function () {
    $section   = Section::factory()->university()->create();
    $professor = Professor::factory()->create();
    $classroom = Classroom::factory()->create();

    $data = [
        'section_id'   => $section->id,
        'professor_id' => $professor->id,
        'classroom_id' => $classroom->id,
        'subject_id'   => $section->subject_id,
        'day_of_week'  => DayOfWeek::Monday->value,
        'start_time'   => '08:00',
        'end_time'     => '08:45',
        'type'         => ScheduleSessionType::Theory->value,
        'valid_from'   => '2000-01-01',
        'valid_until'  => null,
    ];

    $this->actingAs(userWithSchedulePerm('schedules.create'))
        ->withHeader('Accept', 'application/json')
        ->post('/scheduling/schedules', $data)
        ->assertStatus(422)
        ->assertJsonValidationErrorFor('valid_from');
});
