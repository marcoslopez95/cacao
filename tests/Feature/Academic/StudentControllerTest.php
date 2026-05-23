<?php

use App\Enums\EnrollmentStatus;
use App\Enums\PeriodStatus;
use App\Models\Career;
use App\Models\Enrollment;
use App\Models\Pensum;
use App\Models\Period;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

// ---------------------------------------------------------------------------
// Access
// ---------------------------------------------------------------------------

test('unauthenticated user is redirected to login', function () {
    $this->get('/academic/students')
        ->assertRedirect('/login');
});

test('authenticated user can access the students index', function () {
    $this->actingAs(User::factory()->create())
        ->get('/academic/students')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Students/Index')
            ->has('students')
            ->has('careers')
            ->has('quickCounts')
            ->has('filters')
        );
});

// ---------------------------------------------------------------------------
// Listing
// ---------------------------------------------------------------------------

test('students are listed with user and career data', function () {
    $pensum = Pensum::factory()->for(Career::factory())->create();
    $student = Student::factory()->create(['current_pensum_id' => $pensum->id]);

    $this->actingAs(User::factory()->create())
        ->get('/academic/students')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Students/Index')
            ->has('students.data', 1, fn ($row) => $row
                ->where('id', $student->id)
                ->where('name', $student->user->name)
                ->where('email', $student->user->email)
                ->where('career_name', $student->pensum->career->name)
                ->where('gpa', null)
                ->where('cedula', null)
                ->where('code', null)
                ->etc()
            )
        );
});

// ---------------------------------------------------------------------------
// Search filter
// ---------------------------------------------------------------------------

test('search by name returns matching students only', function () {
    $match = Student::factory()->create();
    $match->user->update(['name' => 'Camila Ríos Especial']);

    $other = Student::factory()->create();
    $other->user->update(['name' => 'Pedro González']);

    $this->actingAs(User::factory()->create())
        ->get('/academic/students?search=camila')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('students.data', 1)
            ->where('students.data.0.name', 'Camila Ríos Especial')
        );
});

test('search by email returns matching students only', function () {
    $match = Student::factory()->create();
    $match->user->update(['email' => 'unique.test.email@cacao.edu.ve']);

    Student::factory()->count(3)->create();

    $this->actingAs(User::factory()->create())
        ->get('/academic/students?search=unique.test.email')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('students.data', 1));
});

// ---------------------------------------------------------------------------
// Career filter
// ---------------------------------------------------------------------------

test('career_id filter returns only students in that career', function () {
    $careerA = Career::factory()->create();
    $careerB = Career::factory()->create();

    $pensumA = Pensum::factory()->for($careerA)->create();
    $pensumB = Pensum::factory()->for($careerB)->create();

    Student::factory()->create(['current_pensum_id' => $pensumA->id]);
    Student::factory()->create(['current_pensum_id' => $pensumA->id]);
    Student::factory()->create(['current_pensum_id' => $pensumB->id]);

    $this->actingAs(User::factory()->create())
        ->get("/academic/students?career_id[]={$careerA->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('students.data', 2));
});

// ---------------------------------------------------------------------------
// Academic year filter
// ---------------------------------------------------------------------------

test('academic_year filter returns only students in that year', function () {
    Student::factory()->create(['academic_year' => 1]);
    Student::factory()->create(['academic_year' => 1]);
    Student::factory()->create(['academic_year' => 3]);

    $this->actingAs(User::factory()->create())
        ->get('/academic/students?academic_year[]=1')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('students.data', 2));
});

// ---------------------------------------------------------------------------
// Enrollment status filter
// ---------------------------------------------------------------------------

test('enrollment_status confirmed returns students with confirmed enrollment for active period', function () {
    $period = Period::factory()->create(['status' => PeriodStatus::Active]);

    $pensum = Pensum::factory()->create();

    $confirmed = Student::factory()->create();
    Enrollment::factory()->create([
        'student_id' => $confirmed->id,
        'period_id' => $period->id,
        'pensum_id' => $pensum->id,
        'status' => EnrollmentStatus::Confirmed,
    ]);

    $draft = Student::factory()->create();
    Enrollment::factory()->create([
        'student_id' => $draft->id,
        'period_id' => $period->id,
        'pensum_id' => $pensum->id,
        'status' => EnrollmentStatus::Draft,
    ]);

    $this->actingAs(User::factory()->create())
        ->get('/academic/students?enrollment_status[]=confirmed')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('students.data', 1)
            ->where('students.data.0.id', $confirmed->id)
        );
});

test('enrollment_status none returns students without enrollment for active period', function () {
    $period = Period::factory()->create(['status' => PeriodStatus::Active]);

    $pensum = Pensum::factory()->create();

    $enrolled = Student::factory()->create();
    Enrollment::factory()->create([
        'student_id' => $enrolled->id,
        'period_id' => $period->id,
        'pensum_id' => $pensum->id,
        'status' => EnrollmentStatus::Confirmed,
    ]);

    $noEnrollment = Student::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get('/academic/students?enrollment_status[]=none')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('students.data', 1)
            ->where('students.data.0.id', $noEnrollment->id)
        );
});

// ---------------------------------------------------------------------------
// Quick counts
// ---------------------------------------------------------------------------

test('quick counts include all and newcomers', function () {
    Student::factory()->count(3)->create(['academic_year' => 1]);
    Student::factory()->count(2)->create(['academic_year' => 2]);

    $this->actingAs(User::factory()->create())
        ->get('/academic/students')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('quickCounts.all', 5)
            ->where('quickCounts.newcomers', 3)
            ->where('quickCounts.top', 0)
            ->where('quickCounts.risk', 0)
        );
});

// ---------------------------------------------------------------------------
// Pagination
// ---------------------------------------------------------------------------

test('per_page parameter controls page size', function () {
    Student::factory()->count(30)->create();

    $this->actingAs(User::factory()->create())
        ->get('/academic/students?per_page=10')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('students.meta.per_page', 10)
            ->where('students.meta.total', 30)
            ->has('students.data', 10)
        );
});
