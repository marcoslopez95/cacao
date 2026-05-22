<?php

use App\Models\Enrollment;
use App\Models\Guardian;
use App\Models\Period;
use App\Models\Professor;
use App\Models\Student;
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
