<?php

use App\Models\Period;
use App\Models\Professor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(fn () => app(PermissionRegistrar::class)->forgetCachedPermissions());

test('unauthenticated users are redirected from professor dashboard', function () {
    $this->get(route('professor.dashboard'))
        ->assertRedirect(route('login'));
});

test('professor can access their dashboard and receives correct props', function () {
    $professor = Professor::factory()->create();
    $user = $professor->user;

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('professor.dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('professor/Dashboard')
            ->has('period')
            ->has('sections_count')
            ->has('total_students')
            ->has('hours_per_week')
            ->has('today_label')
            ->has('today_schedules')
        );
});

test('professor dashboard returns null period when no active period exists', function () {
    Period::where('status', 'active')->delete();

    $professor = Professor::factory()->create();
    $user = $professor->user;

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('professor.dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('professor/Dashboard')
            ->where('period', null)
            ->where('sections_count', 0)
            ->where('total_students', 0)
            ->where('hours_per_week', 0)
            ->has('today_schedules')
        );
});
