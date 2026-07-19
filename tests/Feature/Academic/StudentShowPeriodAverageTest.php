<?php

/**
 * Feature tests — student-enrollment-grades (UC-02, UC-03)
 *
 * Covers `StudentShowResource`: `enrollments[].period_average` and the
 * computed `cumulative_gpa` (no longer read from the static column).
 */

use App\Models\Enrollment;
use App\Models\EnrollmentDetail;
use App\Models\GradeConfig;
use App\Models\GradeEntry;
use App\Models\GradeSlot;
use App\Models\Period;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    Role::findOrCreate('Admin', 'web');
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Admin');
});

/**
 * Creates an enrollment with a single subject on a single-slot (weight 100)
 * grade config, so the subject's final grade equals the slot value directly.
 */
function enrollmentWithFinalGrade(Student $student, ?string $slotValue): Enrollment
{
    $period = Period::factory()->active()->create();
    $config = GradeConfig::factory()->university()->create([
        'period_id' => $period->id,
        'passing_value' => '10.00',
    ]);
    $slot = GradeSlot::factory()->create([
        'grade_config_id' => $config->id,
        'weight' => 100,
        'is_remedial' => false,
    ]);

    $enrollment = Enrollment::factory()->create([
        'student_id' => $student->id,
        'period_id' => $period->id,
    ]);

    $section = Section::factory()->create(['period_id' => $period->id]);
    $detail = EnrollmentDetail::factory()->create([
        'enrollment_id' => $enrollment->id,
        'subject_id' => Subject::factory()->create()->id,
        'section_id' => $section->id,
    ]);

    if ($slotValue !== null) {
        GradeEntry::factory()->create([
            'enrollment_detail_id' => $detail->id,
            'grade_slot_id' => $slot->id,
            'value' => $slotValue,
            'is_published' => true,
        ]);
    }

    return $enrollment;
}

test('enrollment history exposes period_average calculated from final grades', function () {
    $student = Student::factory()->create();
    enrollmentWithFinalGrade($student, '16');

    $this->actingAs($this->admin)
        ->get("/academic/students/{$student->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Students/Show')
            ->has('student.enrollments', 1, fn ($e) => $e
                ->where('period_average', '16')
                ->etc()
            )
        );
});

test('period_average is null when no subject has a definitive grade yet', function () {
    $student = Student::factory()->create();
    enrollmentWithFinalGrade($student, null);

    $this->actingAs($this->admin)
        ->get("/academic/students/{$student->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('student.enrollments', 1, fn ($e) => $e
                ->where('period_average', null)
                ->etc()
            )
        );
});

test('cumulative_gpa is computed from period averages, not from the static column', function () {
    // Static column deliberately holds a stale/wrong value — must be ignored.
    $student = Student::factory()->create(['cumulative_gpa' => '5.00']);
    enrollmentWithFinalGrade($student, '16');

    $this->actingAs($this->admin)
        ->get("/academic/students/{$student->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('student.cumulative_gpa', '16')
        );
});

test('cumulative_gpa averages the period_average of every enrollment that has one', function () {
    $student = Student::factory()->create();
    enrollmentWithFinalGrade($student, '16');
    enrollmentWithFinalGrade($student, '10');

    // (16 + 10) / 2 = 13
    $this->actingAs($this->admin)
        ->get("/academic/students/{$student->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('student.cumulative_gpa', '13')
        );
});

test('cumulative_gpa is null when no enrollment has a calculable period_average', function () {
    $student = Student::factory()->create();
    enrollmentWithFinalGrade($student, null);

    $this->actingAs($this->admin)
        ->get("/academic/students/{$student->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('student.cumulative_gpa', null)
        );
});

test('period_average is calculated correctly for a secondary student (GradeConfigResolver enum mapping)', function () {
    $period = Period::factory()->active()->create();
    $config = GradeConfig::factory()->primarySecondary()->create([
        'period_id' => $period->id,
        'passing_value' => '10.00',
    ]);
    $slot = GradeSlot::factory()->create([
        'grade_config_id' => $config->id,
        'weight' => 100,
        'is_remedial' => false,
    ]);

    $student = Student::factory()->secondary()->create();
    $enrollment = Enrollment::factory()->create([
        'student_id' => $student->id,
        'period_id' => $period->id,
    ]);
    $section = Section::factory()->create(['period_id' => $period->id]);
    $detail = EnrollmentDetail::factory()->create([
        'enrollment_id' => $enrollment->id,
        'subject_id' => Subject::factory()->create()->id,
        'section_id' => $section->id,
    ]);
    GradeEntry::factory()->create([
        'enrollment_detail_id' => $detail->id,
        'grade_slot_id' => $slot->id,
        'value' => '16',
        'is_published' => true,
    ]);

    $this->actingAs($this->admin)
        ->get("/academic/students/{$student->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('student.enrollments', 1, fn ($e) => $e
                ->where('period_average', '16')
                ->etc()
            )
        );
});
