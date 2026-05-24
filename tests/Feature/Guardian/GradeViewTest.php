<?php

use App\Models\Catalogs\KinshipType;
use App\Models\Enrollment;
use App\Models\EnrollmentDetail;
use App\Models\GradeConfig;
use App\Models\GradeEntry;
use App\Models\GradeSlot;
use App\Models\Guardian;
use App\Models\Period;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function guardianGradeContext(): array
{
    $guardian = Guardian::factory()->create();
    $student = Student::factory()->create();
    $kinship = KinshipType::firstOrCreate(['code' => 'other'], ['name' => 'Otro', 'active' => true, 'sort_order' => 99]);
    $student->guardians()->attach($guardian->id, ['kinship_type_id' => $kinship->id, 'is_primary' => true, 'is_emergency_contact' => false]);

    $period = Period::factory()->active()->create();
    $config = GradeConfig::factory()->university()->create();
    $slot = GradeSlot::factory()->create([
        'grade_config_id' => $config->id,
        'weight' => '100.00',
    ]);

    $section = Section::factory()->create(['period_id' => $period->id]);
    $enrollment = Enrollment::factory()->create([
        'student_id' => $student->id,
        'period_id' => $period->id,
    ]);
    $detail = EnrollmentDetail::factory()->create([
        'enrollment_id' => $enrollment->id,
        'section_id' => $section->id,
    ]);

    return compact('guardian', 'student', 'detail', 'slot', 'period');
}

// ---------------------------------------------------------------------------
// access control
// ---------------------------------------------------------------------------

test('unauthenticated users are redirected from guardian grades', function () {
    $this->get(route('guardian.grades.index'))->assertRedirect(route('login'));
});

test('student cannot access guardian grades view', function () {
    $student = Student::factory()->create();

    $this->actingAs($student->user)
        ->get(route('guardian.grades.index'))
        ->assertForbidden();
});

test('guardian can view their students grades', function () {
    ['guardian' => $guardian, 'student' => $student] = guardianGradeContext();

    $this->actingAs($guardian->user)
        ->get(route('guardian.grades.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('guardian/Grades/Index')
            ->has('grades')
            ->has('period')
            ->where('student_name', $student->user->name)
        );
});

test('guardian grades page shows null when no active period', function () {
    $guardian = Guardian::factory()->create();
    $kinship = KinshipType::firstOrCreate(['code' => 'other'], ['name' => 'Otro', 'active' => true, 'sort_order' => 99]);
    $s = Student::factory()->create();
    $s->guardians()->attach($guardian->id, ['kinship_type_id' => $kinship->id, 'is_primary' => true, 'is_emergency_contact' => false]);

    Period::where('status', 'active')->delete();

    $this->actingAs($guardian->user)
        ->get(route('guardian.grades.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('grades', null));
});

// ---------------------------------------------------------------------------
// grade data
// ---------------------------------------------------------------------------

test('guardian sees published grades for their student', function () {
    ['guardian' => $guardian, 'detail' => $detail, 'slot' => $slot] = guardianGradeContext();

    GradeEntry::factory()->published()->create([
        'enrollment_detail_id' => $detail->id,
        'grade_slot_id' => $slot->id,
        'value' => 17,
    ]);

    $this->actingAs($guardian->user)
        ->get(route('guardian.grades.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('grades.subjects.0.slots.0.value', '17.00')
        );
});
