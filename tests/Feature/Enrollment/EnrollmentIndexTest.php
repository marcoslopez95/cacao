<?php

use App\Enums\EnrollmentDetailStatus;
use App\Enums\EnrollmentStatus;
use App\Models\Catalogs\KinshipType;
use App\Models\Enrollment;
use App\Models\EnrollmentDetail;
use App\Models\Guardian;
use App\Models\Pensum;
use App\Models\Period;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helper
// ---------------------------------------------------------------------------

function makeStudentWithPensumAndSection(): array
{
    $period = Period::factory()->active()->create();
    $pensum = Pensum::factory()->create();
    $subject = Subject::factory()->create(['pensum_id' => $pensum->id, 'period_number' => 1]);
    $section = Section::factory()->create(['subject_id' => $subject->id, 'period_id' => $period->id, 'capacity' => 30]);
    Schedule::factory()->create(['section_id' => $section->id, 'subject_id' => $subject->id]);
    $student = Student::factory()->create(['current_pensum_id' => $pensum->id, 'academic_year' => 1]);

    return compact('period', 'pensum', 'subject', 'section', 'student');
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

test('student sees enrollment index with catalog', function () {
    $this->withoutVite();
    ['student' => $student] = makeStudentWithPensumAndSection();

    $this->actingAs($student->user)
        ->get('/enrollment')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('enrollment/Index')
            ->has('enrollment')
            ->has('catalog', 1)
            ->has('rules')
            ->has('can')
        );
});

test('student with no active period sees empty catalog', function () {
    $this->withoutVite();
    $pensum = Pensum::factory()->create();
    $student = Student::factory()->create(['current_pensum_id' => $pensum->id]);

    $this->actingAs($student->user)
        ->get('/enrollment')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('enrollment/Index')
            ->where('enrollment', null)
            ->where('catalog', [])
        );
});

test('student with no pensum sees empty catalog', function () {
    $this->withoutVite();
    Period::factory()->active()->create();
    $student = Student::factory()->create(['current_pensum_id' => null]);

    $this->actingAs($student->user)
        ->get('/enrollment')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('enrollment', null)
            ->where('catalog', [])
        );
});

test('guardian sees enrollment for assigned student via student_id param', function () {
    $this->withoutVite();
    ['student' => $student] = makeStudentWithPensumAndSection();
    $guardian = Guardian::factory()->create();
    $kinship = KinshipType::firstOrCreate(['code' => 'other'], ['name' => 'Otro', 'active' => true, 'sort_order' => 99]);
    $student->guardians()->attach($guardian->id, ['kinship_type_id' => $kinship->id, 'is_primary' => true, 'is_emergency_contact' => false]);

    $this->actingAs($guardian->user)
        ->get("/enrollment?student_id={$student->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('enrollment/Index')
            ->has('catalog', 1)
        );
});

test('guardian without student_id sees first assigned student', function () {
    $this->withoutVite();
    ['student' => $student] = makeStudentWithPensumAndSection();
    $guardian = Guardian::factory()->create();
    $kinship = KinshipType::firstOrCreate(['code' => 'other'], ['name' => 'Otro', 'active' => true, 'sort_order' => 99]);
    $student->guardians()->attach($guardian->id, ['kinship_type_id' => $kinship->id, 'is_primary' => true, 'is_emergency_contact' => false]);

    $this->actingAs($guardian->user)
        ->get('/enrollment')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('enrollment/Index')
            ->has('catalog')
        );
});

test('guardian with unassigned student_id gets 403', function () {
    $this->withoutVite();
    $guardian = Guardian::factory()->create();
    $stranger = Student::factory()->create();

    $this->actingAs($guardian->user)
        ->get("/enrollment?student_id={$stranger->id}")
        ->assertForbidden();
});

test('user without student or guardian gets 403', function () {
    $this->withoutVite();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/enrollment')
        ->assertForbidden();
});

test('index auto-creates draft enrollment when none exists', function () {
    $this->withoutVite();
    ['student' => $student, 'period' => $period] = makeStudentWithPensumAndSection();

    $this->assertDatabaseMissing('enrollments', ['student_id' => $student->id]);

    $this->actingAs($student->user)->get('/enrollment')->assertOk();

    $this->assertDatabaseHas('enrollments', [
        'student_id' => $student->id,
        'period_id' => $period->id,
        'status' => EnrollmentStatus::Draft->value,
    ]);
});

test('index does not create duplicate draft for same period', function () {
    $this->withoutVite();
    ['student' => $student, 'period' => $period, 'pensum' => $pensum] = makeStudentWithPensumAndSection();

    Enrollment::factory()->create([
        'student_id' => $student->id,
        'period_id' => $period->id,
        'pensum_id' => $pensum->id,
        'status' => EnrollmentStatus::Draft,
    ]);

    $this->actingAs($student->user)->get('/enrollment')->assertOk();

    $this->assertDatabaseCount('enrollments', 1);
});

test('catalog shows prereqs_ok false when prerequisite not completed', function () {
    $this->withoutVite();
    $period = Period::factory()->active()->create();
    $pensum = Pensum::factory()->create();
    $prereq = Subject::factory()->create(['pensum_id' => $pensum->id, 'period_number' => 1]);
    $subject = Subject::factory()->create(['pensum_id' => $pensum->id, 'period_number' => 2]);
    $subject->prerequisites()->attach($prereq->id);
    $section = Section::factory()->create(['subject_id' => $subject->id, 'period_id' => $period->id]);
    Schedule::factory()->create(['section_id' => $section->id, 'subject_id' => $subject->id]);
    $student = Student::factory()->create(['current_pensum_id' => $pensum->id]);

    $this->actingAs($student->user)
        ->get('/enrollment')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('catalog', 1)
            ->where('catalog.0.prereqs_ok', false)
        );
})->skip('Requires grades table — implement when grades module is ready');

test('catalog shows selected_section_id for existing draft detail', function () {
    $this->withoutVite();
    ['student' => $student, 'period' => $period, 'pensum' => $pensum, 'subject' => $subject, 'section' => $section] = makeStudentWithPensumAndSection();

    $enrollment = Enrollment::factory()->create([
        'student_id' => $student->id,
        'period_id' => $period->id,
        'pensum_id' => $pensum->id,
    ]);

    EnrollmentDetail::factory()->create([
        'enrollment_id' => $enrollment->id,
        'subject_id' => $subject->id,
        'section_id' => $section->id,
        'status' => EnrollmentDetailStatus::Draft,
    ]);

    $this->actingAs($student->user)
        ->get('/enrollment')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('catalog.0.selected_section_id', $section->id)
        );
});
