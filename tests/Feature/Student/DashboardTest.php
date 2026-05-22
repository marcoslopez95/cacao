<?php

use App\Models\Period;
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
