<?php

use App\Enums\GradeVisibility;
use App\Models\Enrollment;
use App\Models\EnrollmentDetail;
use App\Models\GradeConfig;
use App\Models\GradeEntry;
use App\Models\GradeSlot;
use App\Models\Period;
use App\Models\Professor;
use App\Models\Section;
use App\Models\Student;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function studentGradeContext(): array
{
    $period = Period::factory()->active()->create();
    $config = GradeConfig::factory()->university()->create();
    $slot = GradeSlot::factory()->create([
        'grade_config_id' => $config->id,
        'weight' => '100.00',
    ]);

    $section = Section::factory()->create(['period_id' => $period->id]);
    $student = Student::factory()->create();
    $enrollment = Enrollment::factory()->create([
        'student_id' => $student->id,
        'period_id' => $period->id,
    ]);
    $detail = EnrollmentDetail::factory()->create([
        'enrollment_id' => $enrollment->id,
        'section_id' => $section->id,
    ]);

    return compact('student', 'detail', 'slot', 'period');
}

// ---------------------------------------------------------------------------
// access control
// ---------------------------------------------------------------------------

test('unauthenticated users are redirected from student grades', function () {
    $this->get(route('student.grades.index'))->assertRedirect(route('login'));
});

test('professor cannot access student grades view', function () {
    $professor = Professor::factory()->create();

    $this->actingAs($professor->user)
        ->get(route('student.grades.index'))
        ->assertForbidden();
});

test('student can view their grades page', function () {
    ['student' => $student] = studentGradeContext();

    $this->actingAs($student->user)
        ->get(route('student.grades.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('student/Grades/Index')
            ->has('grades')
            ->has('period')
        );
});

// ---------------------------------------------------------------------------
// visibility modes
// ---------------------------------------------------------------------------

test('student with real_time visibility sees all entries including unpublished', function () {
    ['student' => $student, 'detail' => $detail, 'slot' => $slot] = studentGradeContext();

    $team = Team::factory()->create(['grade_visibility' => GradeVisibility::RealTime]);
    $student->user->teams()->attach($team, ['role' => 'member']);
    $student->user->update(['current_team_id' => $team->id]);

    GradeEntry::factory()->unpublished()->create([
        'enrollment_detail_id' => $detail->id,
        'grade_slot_id' => $slot->id,
        'value' => 14,
    ]);

    $this->actingAs($student->user)
        ->get(route('student.grades.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('grades.subjects.0.slots.0.value', '14.00')
        );
});

test('student with manual visibility only sees published entries', function () {
    ['student' => $student, 'detail' => $detail, 'slot' => $slot] = studentGradeContext();

    $team = Team::factory()->create(['grade_visibility' => GradeVisibility::Manual]);
    $student->user->teams()->attach($team, ['role' => 'member']);
    $student->user->update(['current_team_id' => $team->id]);

    GradeEntry::factory()->unpublished()->create([
        'enrollment_detail_id' => $detail->id,
        'grade_slot_id' => $slot->id,
        'value' => 14,
    ]);

    $this->actingAs($student->user)
        ->get(route('student.grades.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('grades.subjects.0.slots.0.value', null)
        );
});

test('student with no active period receives null grades', function () {
    $student = Student::factory()->create();

    Period::where('status', 'active')->delete();

    $this->actingAs($student->user)
        ->get(route('student.grades.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('grades', null));
});
