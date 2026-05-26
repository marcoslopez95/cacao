<?php

/**
 * Acceptance tests — student-academic-show
 *
 * UC-01: Admin puede ver el perfil académico de un estudiante
 * UC-02: Botón lápiz en Index navega a /security/users/{user_id}/edit (StudentListResource expone user_id)
 *
 * Estos tests son el contrato del senior_tester.
 * NO modificar — reportar al leader si parecen incorrectos.
 */

use App\Enums\EducationalLevel;
use App\Enums\EnrollmentStatus;
use App\Models\Career;
use App\Models\Catalogs\KinshipType;
use App\Models\Enrollment;
use App\Models\Guardian;
use App\Models\Pensum;
use App\Models\Period;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->admin = User::factory()->create();
});

// ---------------------------------------------------------------------------
// UC-02: StudentListResource expone user_id (necesario para botón Editar)
// ---------------------------------------------------------------------------

test('StudentListResource exposes user_id for each student row', function () {
    $student = Student::factory()->create();

    $this->actingAs($this->admin)
        ->get('/academic/students')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Students/Index')
            ->has('students.data', 1, fn ($row) => $row
                ->where('id', $student->id)
                ->where('user_id', $student->user_id)
                ->etc()
            )
        );
});

// ---------------------------------------------------------------------------
// UC-01: Ruta GET /academic/students/{id} existe y devuelve 200
// ---------------------------------------------------------------------------

test('GET /academic/students/{id} returns 200 for authenticated user', function () {
    $student = Student::factory()->create();

    $this->actingAs($this->admin)
        ->get("/academic/students/{$student->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Students/Show')
        );
});

test('GET /academic/students/{id} returns 404 for non-existent student', function () {
    $this->actingAs($this->admin)
        ->get('/academic/students/99999')
        ->assertNotFound();
});

// ---------------------------------------------------------------------------
// UC-01: Show page contains identity fields
// ---------------------------------------------------------------------------

test('show page exposes identity fields for university student', function () {
    $career = Career::factory()->create(['name' => 'Ingeniería en Sistemas']);
    $pensum = Pensum::factory()->for($career)->create(['name' => 'Plan 2020']);
    $student = Student::factory()->create([
        'educational_level' => EducationalLevel::University,
        'current_pensum_id' => $pensum->id,
        'academic_year' => 2,
    ]);

    $this->actingAs($this->admin)
        ->get("/academic/students/{$student->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Students/Show')
            ->has('student', fn ($s) => $s
                ->where('id', $student->id)
                ->where('user_id', $student->user_id)
                ->where('name', $student->user->name)
                ->where('email', $student->user->email)
                ->where('educational_level', 'university')
                ->where('academic_year', 2)
                ->where('career_name', 'Ingeniería en Sistemas')
                ->where('pensum_name', 'Plan 2020')
                ->etc()
            )
        );
});

// ---------------------------------------------------------------------------
// UC-01: Show page for primary student includes guardians
// ---------------------------------------------------------------------------

test('show page for primary student includes guardians array', function () {
    $kinship = KinshipType::firstOrCreate(
        ['code' => 'mother'],
        ['name' => 'Madre', 'active' => true, 'sort_order' => 1]
    );
    $guardian = Guardian::factory()->create();
    $student = Student::factory()->primary()->create();
    $student->guardians()->attach($guardian->id, [
        'kinship_type_id' => $kinship->id,
        'is_primary' => true,
        'is_emergency_contact' => true,
    ]);

    $this->actingAs($this->admin)
        ->get("/academic/students/{$student->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Students/Show')
            ->has('student', fn ($s) => $s
                ->where('educational_level', 'primary')
                ->has('guardians', 1, fn ($g) => $g
                    ->where('name', $guardian->user->name)
                    ->etc()
                )
                ->etc()
            )
        );
});

// ---------------------------------------------------------------------------
// UC-01: Active enrollment appears in show data
// ---------------------------------------------------------------------------

test('show page includes active_enrollment when student has enrollment in active period', function () {
    $period = Period::factory()->active()->create();
    $pensum = Pensum::factory()->create();
    $student = Student::factory()->create([
        'educational_level' => EducationalLevel::University,
        'current_pensum_id' => $pensum->id,
    ]);
    Enrollment::factory()->create([
        'student_id' => $student->id,
        'period_id' => $period->id,
        'pensum_id' => $pensum->id,
        'status' => EnrollmentStatus::Confirmed,
        'uc_inscritas' => 18,
        'uc_disponibles' => 24,
    ]);

    $this->actingAs($this->admin)
        ->get("/academic/students/{$student->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Students/Show')
            ->has('student', fn ($s) => $s
                ->has('active_enrollment')
                ->where('active_enrollment.status', 'confirmed')
                ->where('active_enrollment.uc_inscritas', 18)
                ->etc()
            )
        );
});

// ---------------------------------------------------------------------------
// UC-01: active_enrollment is null when no active period enrollment
// ---------------------------------------------------------------------------

test('show page has null active_enrollment when student has no active period enrollment', function () {
    $student = Student::factory()->create();

    $this->actingAs($this->admin)
        ->get("/academic/students/{$student->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Students/Show')
            ->has('student', fn ($s) => $s
                ->where('active_enrollment', null)
                ->has('enrollments')
                ->etc()
            )
        );
});

// ---------------------------------------------------------------------------
// Unauthenticated access is blocked
// ---------------------------------------------------------------------------

test('unauthenticated user is redirected to login when accessing show', function () {
    $student = Student::factory()->create();

    $this->get("/academic/students/{$student->id}")
        ->assertRedirect('/login');
});
