<?php

/**
 * Feature tests — student-enrollment-grades (UC-01)
 *
 * Covers `GET academic/students/{student}/enrollments/{enrollment}`.
 */

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

test('unauthenticated user is redirected to login', function () {
    $student = Student::factory()->create();
    $enrollment = Enrollment::factory()->create(['student_id' => $student->id]);

    $this->get("/academic/students/{$student->id}/enrollments/{$enrollment->id}")
        ->assertRedirect('/login');
});

test('admin sees the grade breakdown for an enrollment belonging to the student', function () {
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

    $student = Student::factory()->create();
    $enrollment = Enrollment::factory()->create([
        'student_id' => $student->id,
        'period_id' => $period->id,
    ]);
    $section = Section::factory()->create(['period_id' => $period->id]);
    $subject = Subject::factory()->create(['name' => 'Cálculo I']);
    $detail = EnrollmentDetail::factory()->create([
        'enrollment_id' => $enrollment->id,
        'subject_id' => $subject->id,
        'section_id' => $section->id,
    ]);
    GradeEntry::factory()->create([
        'enrollment_detail_id' => $detail->id,
        'grade_slot_id' => $slot->id,
        'value' => 16,
        'is_published' => false, // admin sees it anyway — always RealTime visibility
    ]);

    $this->actingAs($this->admin)
        ->get("/academic/students/{$student->id}/enrollments/{$enrollment->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Students/EnrollmentGrades')
            ->where('student.id', $student->id)
            ->has('grades.subjects', 1, fn ($s) => $s
                ->where('subject_name', 'Cálculo I')
                ->where('final_grade', '16')
                ->etc()
            )
        );
});

test('returns 404 when the enrollment does not belong to the student in the route', function () {
    $student = Student::factory()->create();
    $otherStudent = Student::factory()->create();
    $enrollment = Enrollment::factory()->create(['student_id' => $otherStudent->id]);

    $this->actingAs($this->admin)
        ->get("/academic/students/{$student->id}/enrollments/{$enrollment->id}")
        ->assertNotFound();
});

test('returns 404 when the enrollment does not exist', function () {
    $student = Student::factory()->create();

    $this->actingAs($this->admin)
        ->get("/academic/students/{$student->id}/enrollments/99999")
        ->assertNotFound();
});

test('student who owns the enrollment gets 403, this route is admin-only', function () {
    $student = Student::factory()->create();
    $enrollment = Enrollment::factory()->create(['student_id' => $student->id]);

    $this->actingAs($student->user)
        ->get("/academic/students/{$student->id}/enrollments/{$enrollment->id}")
        ->assertForbidden();
});

test('guardian of the student gets 403, this route is admin-only', function () {
    $kinship = KinshipType::firstOrCreate(
        ['code' => 'other'],
        ['name' => 'Otro', 'active' => true, 'sort_order' => 99]
    );

    $student = Student::factory()->create();
    $enrollment = Enrollment::factory()->create(['student_id' => $student->id]);
    $guardian = Guardian::factory()->create();
    $student->guardians()->attach($guardian->id, [
        'kinship_type_id' => $kinship->id,
        'is_primary' => true,
        'is_emergency_contact' => true,
    ]);

    $this->actingAs($guardian->user)
        ->get("/academic/students/{$student->id}/enrollments/{$enrollment->id}")
        ->assertForbidden();
});
