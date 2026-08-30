<?php

use App\Models\Catalogs\KinshipType;
use App\Models\Enrollment;
use App\Models\Guardian;
use App\Models\Period;
use App\Models\Professor;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(fn () => app(PermissionRegistrar::class)->forgetCachedPermissions());

test('unauthenticated user is redirected from student dashboard', function () {
    $this->get(route('student.dashboard'))
        ->assertRedirect(route('login'));
});

test('student dashboard returns 200 with all required props', function () {
    $student = Student::factory()->create();
    $user = $student->user;

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('student.dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('student/Dashboard')
            ->has('period')
            ->has('enrollment')
            ->has('subjects_count')
            ->has('uc_pensum')
            ->has('uc_aprobadas')
            ->has('today_label')
            ->has('today_schedules')
            ->has('guardians')
        );
});

test('student dashboard guardians is empty when student has no guardians', function () {
    $student = Student::factory()->create();
    $user = $student->user;

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('student.dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('student/Dashboard')
            ->where('guardians', [])
        );
});

test('student dashboard lists primary guardian first', function () {
    $kinship = KinshipType::firstOrCreate(['code' => 'other'], ['name' => 'Otro', 'active' => true, 'sort_order' => 99]);

    $student = Student::factory()->create();
    $user = $student->user;

    $secondary = Guardian::factory()->create();
    $primary = Guardian::factory()->create();

    $student->guardians()->attach($secondary->id, [
        'kinship_type_id' => $kinship->id,
        'is_primary' => false,
        'is_emergency_contact' => false,
    ]);
    $student->guardians()->attach($primary->id, [
        'kinship_type_id' => $kinship->id,
        'is_primary' => true,
        'is_emergency_contact' => false,
    ]);

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('student.dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('student/Dashboard')
            ->has('guardians', 2)
            ->where('guardians.0.email', $primary->user->email)
            ->where('guardians.0.is_primary', true)
            ->where('guardians.1.is_primary', false)
        );
});

test('student dashboard enrollment is null when no active period', function () {
    Period::where('status', 'active')->delete();

    $student = Student::factory()->create();
    $user = $student->user;

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('student.dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('student/Dashboard')
            ->where('period', null)
            ->where('enrollment', null)
            ->where('subjects_count', 0)
            ->where('uc_pensum', 0)
        );
});

test('student dashboard enrollment is null when no enrollment for active period', function () {
    Period::factory()->active()->create();

    $student = Student::factory()->create();
    $user = $student->user;

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('student.dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('student/Dashboard')
            ->where('enrollment', null)
            ->where('subjects_count', 0)
        );
});

test('student dashboard shows active enrollment data when enrollment exists', function () {
    $period = Period::factory()->active()->create();
    $student = Student::factory()->create();
    $user = $student->user;

    Enrollment::factory()->create([
        'student_id' => $student->id,
        'period_id' => $period->id,
        'uc_inscritas' => 12,
    ]);

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('student.dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('student/Dashboard')
            ->has('enrollment')
            ->whereNot('enrollment', null)
            ->where('enrollment.uc_inscritas', 12)
        );
});

test('student dashboard returns 200 on sunday with no school-day label', function () {
    $this->travelTo(Carbon::parse('2024-01-07 09:00:00'));

    $student = Student::factory()->create();
    $user = $student->user;

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('student.dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('student/Dashboard')
            ->where('today_label', 'Domingo')
            ->where('today_schedules', [])
        );
});

test('student cannot access professor portal', function () {
    $student = Student::factory()->create();
    $user = $student->user;

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('professor.dashboard'))
        ->assertForbidden();
});

test('student cannot access guardian portal', function () {
    $student = Student::factory()->create();
    $user = $student->user;

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('guardian.dashboard'))
        ->assertForbidden();
});

test('professor cannot access student portal', function () {
    $professor = Professor::factory()->create();
    $user = $professor->user;

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('student.dashboard'))
        ->assertForbidden();
});

test('guardian cannot access student portal', function () {
    $guardian = Guardian::factory()->create();
    $user = $guardian->user;

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('student.dashboard'))
        ->assertForbidden();
});
