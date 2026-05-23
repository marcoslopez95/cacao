<?php

use App\Models\Enrollment;
use App\Models\EnrollmentDetail;
use App\Models\GradeConfig;
use App\Models\GradeEntry;
use App\Models\GradeSlot;
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

/**
 * Sets up a student with enrollment and grade entries.
 *
 * @return array{student: Student, detail: EnrollmentDetail, slots: GradeSlot[]}
 */
function calcContext(array $slotWeights = [50, 50], bool $withRemedial = false): array
{
    $period = Period::factory()->active()->create();
    $config = GradeConfig::factory()->university()->create(['passing_value' => '10.00']);
    $slots = [];

    foreach ($slotWeights as $i => $weight) {
        $slots[] = GradeSlot::factory()->create([
            'grade_config_id' => $config->id,
            'weight' => $weight,
            'sort_order' => $i + 1,
            'is_remedial' => false,
        ]);
    }

    if ($withRemedial) {
        $slots[] = GradeSlot::factory()->remedial()->create([
            'grade_config_id' => $config->id,
            'sort_order' => count($slotWeights) + 1,
        ]);
    }

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

    return compact('student', 'detail', 'slots', 'period');
}

// ---------------------------------------------------------------------------
// Final grade — period mode (all slots have values)
// ---------------------------------------------------------------------------

test('final grade is null when not all slots have values', function () {
    ['student' => $student, 'detail' => $detail, 'slots' => $slots] = calcContext([50, 50]);

    // Only one slot has a value
    GradeEntry::factory()->create([
        'enrollment_detail_id' => $detail->id,
        'grade_slot_id' => $slots[0]->id,
        'value' => 15,
        'is_published' => true,
    ]);

    $this->actingAs($student->user)
        ->get(route('student.grades.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('grades.subjects.0.final_grade', null)
        );
});

test('final grade is the weighted average of non-remedial slots', function () {
    ['student' => $student, 'detail' => $detail, 'slots' => $slots] = calcContext([30, 30, 40]);

    GradeEntry::factory()->create([
        'enrollment_detail_id' => $detail->id,
        'grade_slot_id' => $slots[0]->id,
        'value' => 10,
        'is_published' => true,
    ]);
    GradeEntry::factory()->create([
        'enrollment_detail_id' => $detail->id,
        'grade_slot_id' => $slots[1]->id,
        'value' => 16,
        'is_published' => true,
    ]);
    GradeEntry::factory()->create([
        'enrollment_detail_id' => $detail->id,
        'grade_slot_id' => $slots[2]->id,
        'value' => 20,
        'is_published' => true,
    ]);

    // Expected: 10*0.3 + 16*0.3 + 20*0.4 = 3 + 4.8 + 8 = 15.8
    $this->actingAs($student->user)
        ->get(route('student.grades.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('grades.subjects.0.final_grade', '15.8')
            ->where('grades.subjects.0.passed', true)
        );
});

test('student is marked as failed when grade is below passing value', function () {
    ['student' => $student, 'detail' => $detail, 'slots' => $slots] = calcContext([50, 50]);

    GradeEntry::factory()->create([
        'enrollment_detail_id' => $detail->id,
        'grade_slot_id' => $slots[0]->id,
        'value' => 8,
        'is_published' => true,
    ]);
    GradeEntry::factory()->create([
        'enrollment_detail_id' => $detail->id,
        'grade_slot_id' => $slots[1]->id,
        'value' => 8,
        'is_published' => true,
    ]);

    // 8 < 10 passing value
    $this->actingAs($student->user)
        ->get(route('student.grades.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('grades.subjects.0.passed', false)
        );
});

// ---------------------------------------------------------------------------
// Remedial slot
// ---------------------------------------------------------------------------

test('remedial grade elevates final grade when higher than regular average', function () {
    ['student' => $student, 'detail' => $detail, 'slots' => $slots] = calcContext([100], withRemedial: true);

    $regularSlot = $slots[0];
    $remedialSlot = $slots[1];

    // Regular grade: 6 (failing)
    GradeEntry::factory()->create([
        'enrollment_detail_id' => $detail->id,
        'grade_slot_id' => $regularSlot->id,
        'value' => 6,
        'is_published' => true,
    ]);

    // Remedial: 12 (passing)
    GradeEntry::factory()->create([
        'enrollment_detail_id' => $detail->id,
        'grade_slot_id' => $remedialSlot->id,
        'value' => 12,
        'is_published' => true,
    ]);

    // Final should be max(6, 12) = 12
    $this->actingAs($student->user)
        ->get(route('student.grades.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('grades.subjects.0.final_grade', '12')
            ->where('grades.subjects.0.passed', true)
        );
});

test('remedial does not lower final grade when regular average is higher', function () {
    ['student' => $student, 'detail' => $detail, 'slots' => $slots] = calcContext([100], withRemedial: true);

    $regularSlot = $slots[0];
    $remedialSlot = $slots[1];

    // Regular: 18, Remedial: 11 — final stays 18
    GradeEntry::factory()->create([
        'enrollment_detail_id' => $detail->id,
        'grade_slot_id' => $regularSlot->id,
        'value' => 18,
        'is_published' => true,
    ]);
    GradeEntry::factory()->create([
        'enrollment_detail_id' => $detail->id,
        'grade_slot_id' => $remedialSlot->id,
        'value' => 11,
        'is_published' => true,
    ]);

    $this->actingAs($student->user)
        ->get(route('student.grades.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('grades.subjects.0.final_grade', '18')
        );
});
