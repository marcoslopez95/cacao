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

/**
 * Creates a test context: professor, section, active period, grade config, student enrollment.
 *
 * @return array{professor: Professor, section: Section, period: Period, config: GradeConfig, slot: GradeSlot, detail: EnrollmentDetail}
 */
function gradeEntryContext(): array
{
    $professor = Professor::factory()->create();
    $period = Period::factory()->active()->create();
    $section = Section::factory()->create([
        'period_id' => $period->id,
        'main_teacher_id' => $professor->id,
    ]);
    $config = GradeConfig::factory()->university()->create();
    $slot = GradeSlot::factory()->create([
        'grade_config_id' => $config->id,
        'weight' => '100.00',
    ]);

    $student = Student::factory()->create();
    $enrollment = Enrollment::factory()->create([
        'student_id' => $student->id,
        'period_id' => $period->id,
    ]);
    $detail = EnrollmentDetail::factory()->create([
        'enrollment_id' => $enrollment->id,
        'section_id' => $section->id,
    ]);

    return compact('professor', 'section', 'period', 'config', 'slot', 'detail');
}

// ---------------------------------------------------------------------------
// sheet (grade sheet view)
// ---------------------------------------------------------------------------

test('unauthenticated users are redirected from the grade sheet', function () {
    $section = Section::factory()->create();

    $this->get(route('professor.grades.sheet', $section))->assertRedirect(route('login'));
});

test('professor can view grade sheet for their section', function () {
    ['professor' => $professor, 'section' => $section] = gradeEntryContext();

    $this->actingAs($professor->user)
        ->get(route('professor.grades.sheet', $section))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('professor/Grades/Sheet')
            ->has('sheet')
            ->has('lapses')
            ->has('visibility')
        );
});

test('professor cannot view grade sheet for another professors section', function () {
    ['section' => $section] = gradeEntryContext();
    $other = Professor::factory()->create();

    $this->actingAs($other->user)
        ->get(route('professor.grades.sheet', $section))
        ->assertForbidden();
});

test('non-professor student cannot view grade sheet', function () {
    ['section' => $section] = gradeEntryContext();
    $student = Student::factory()->create();

    $this->actingAs($student->user)
        ->get(route('professor.grades.sheet', $section))
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// upsertEntry — direct grade
// ---------------------------------------------------------------------------

test('professor can create a grade entry for a student in their section', function () {
    ['professor' => $professor, 'section' => $section, 'slot' => $slot, 'detail' => $detail] = gradeEntryContext();

    $this->actingAs($professor->user)
        ->put(route('professor.grades.entries.upsert', $section), [
            'enrollment_detail_id' => $detail->id,
            'grade_slot_id' => $slot->id,
            'lapse_id' => null,
            'parent_id' => null,
            'value' => 15.5,
        ])
        ->assertOk()
        ->assertJson(['value' => '15.50']);

    expect(GradeEntry::where('enrollment_detail_id', $detail->id)->exists())->toBeTrue();
});

test('professor can update an existing grade entry', function () {
    ['professor' => $professor, 'section' => $section, 'slot' => $slot, 'detail' => $detail] = gradeEntryContext();

    GradeEntry::factory()->create([
        'enrollment_detail_id' => $detail->id,
        'grade_slot_id' => $slot->id,
        'value' => 10,
    ]);

    $this->actingAs($professor->user)
        ->put(route('professor.grades.entries.upsert', $section), [
            'enrollment_detail_id' => $detail->id,
            'grade_slot_id' => $slot->id,
            'lapse_id' => null,
            'parent_id' => null,
            'value' => 18,
        ])
        ->assertOk()
        ->assertJson(['value' => '18.00']);

    expect(GradeEntry::where('enrollment_detail_id', $detail->id)->count())->toBe(1);
});

test('sub-entry upsert recalculates parent value', function () {
    ['professor' => $professor, 'section' => $section, 'slot' => $slot, 'detail' => $detail] = gradeEntryContext();

    $parent = GradeEntry::factory()->create([
        'enrollment_detail_id' => $detail->id,
        'grade_slot_id' => $slot->id,
        'value' => null,
    ]);

    // Two sub-entries each with 50% weight
    $this->actingAs($professor->user)
        ->put(route('professor.grades.entries.upsert', $section), [
            'enrollment_detail_id' => $detail->id,
            'grade_slot_id' => $slot->id,
            'lapse_id' => null,
            'parent_id' => $parent->id,
            'name' => 'Quiz 1',
            'weight' => 50,
            'value' => 16,
        ])
        ->assertOk();

    $this->actingAs($professor->user)
        ->put(route('professor.grades.entries.upsert', $section), [
            'enrollment_detail_id' => $detail->id,
            'grade_slot_id' => $slot->id,
            'lapse_id' => null,
            'parent_id' => $parent->id,
            'name' => 'Quiz 2',
            'weight' => 50,
            'value' => 20,
        ])
        ->assertOk();

    // Parent should now be (16*50 + 20*50) / 100 = 18
    expect((float) $parent->fresh()->value)->toBe(18.0);
});

// ---------------------------------------------------------------------------
// visibility
// ---------------------------------------------------------------------------

test('entry is published by default when team uses real_time visibility', function () {
    ['professor' => $professor, 'section' => $section, 'slot' => $slot, 'detail' => $detail] = gradeEntryContext();

    $team = Team::factory()->create(['grade_visibility' => GradeVisibility::RealTime]);
    $professor->user->teams()->attach($team, ['role' => 'admin']);
    $professor->user->update(['current_team_id' => $team->id]);

    $this->actingAs($professor->user)
        ->put(route('professor.grades.entries.upsert', $section), [
            'enrollment_detail_id' => $detail->id,
            'grade_slot_id' => $slot->id,
            'lapse_id' => null,
            'parent_id' => null,
            'value' => 14,
        ])
        ->assertOk()
        ->assertJson(['is_published' => true]);
});

test('entry is not published when team uses manual visibility', function () {
    ['professor' => $professor, 'section' => $section, 'slot' => $slot, 'detail' => $detail] = gradeEntryContext();

    $team = Team::factory()->create(['grade_visibility' => GradeVisibility::Manual]);
    $professor->user->teams()->attach($team, ['role' => 'admin']);
    $professor->user->update(['current_team_id' => $team->id]);

    $this->actingAs($professor->user)
        ->put(route('professor.grades.entries.upsert', $section), [
            'enrollment_detail_id' => $detail->id,
            'grade_slot_id' => $slot->id,
            'lapse_id' => null,
            'parent_id' => null,
            'value' => 14,
        ])
        ->assertOk()
        ->assertJson(['is_published' => false]);
});

// ---------------------------------------------------------------------------
// publishSlot
// ---------------------------------------------------------------------------

test('professor can publish all entries for a slot', function () {
    ['professor' => $professor, 'section' => $section, 'slot' => $slot, 'detail' => $detail] = gradeEntryContext();

    GradeEntry::factory()->unpublished()->create([
        'enrollment_detail_id' => $detail->id,
        'grade_slot_id' => $slot->id,
    ]);

    $this->actingAs($professor->user)
        ->post(route('professor.grades.publish', $section), [
            'grade_slot_id' => $slot->id,
            'lapse_id' => null,
        ])
        ->assertRedirect();

    expect(GradeEntry::where('grade_slot_id', $slot->id)->where('is_published', false)->count())->toBe(0);
});
