<?php

use App\Models\Professor;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Role::firstOrCreate(['name' => 'Profesor', 'guard_name' => 'web']);

    foreach (['professors.view', 'professors.create', 'professors.update', 'professors.delete'] as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
    }
});

function userWithProfessorPerm(string $permission): User
{
    $user = User::factory()->create();
    $user->givePermissionTo($permission);

    return $user;
}

// ---------------------------------------------------------------------------
// index
// ---------------------------------------------------------------------------

test('admin can list professors', function () {
    Professor::factory()->count(2)->create();

    $this->actingAs(userWithProfessorPerm('professors.view'))
        ->get('/scheduling/professors')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('scheduling/Professors/Index', false)
            ->has('professors', 2)
            ->has('availableUsers')
        );
});

test('unauthenticated user cannot access professors', function () {
    $this->get('/scheduling/professors')->assertRedirect('/login');
});

test('user without permission cannot list professors', function () {
    $this->actingAs(User::factory()->create())
        ->get('/scheduling/professors')
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// store
// ---------------------------------------------------------------------------

test('admin can create a professor', function () {
    $user = User::factory()->create();
    $user->assignRole('Profesor');

    $this->actingAs(userWithProfessorPerm('professors.create'))
        ->post('/scheduling/professors', [
            'user_id'           => $user->id,
            'weekly_hour_limit' => 20,
        ])
        ->assertRedirect(route('scheduling.professors.index'));

    expect(Professor::where('user_id', $user->id)->exists())->toBeTrue();
});

test('cannot create professor for user without Profesor role', function () {
    $user = User::factory()->create();

    $this->actingAs(userWithProfessorPerm('professors.create'))
        ->post('/scheduling/professors', [
            'user_id'           => $user->id,
            'weekly_hour_limit' => 20,
        ])
        ->assertSessionHasErrors('user_id');
});

test('cannot create duplicate professor for same user', function () {
    $professor = Professor::factory()->create();

    $this->actingAs(userWithProfessorPerm('professors.create'))
        ->post('/scheduling/professors', [
            'user_id'           => $professor->user_id,
            'weekly_hour_limit' => 20,
        ])
        ->assertSessionHasErrors('user_id');
});

test('weekly_hour_limit must be at least 1', function () {
    $user = User::factory()->create();
    $user->assignRole('Profesor');

    $this->actingAs(userWithProfessorPerm('professors.create'))
        ->post('/scheduling/professors', [
            'user_id'           => $user->id,
            'weekly_hour_limit' => 0,
        ])
        ->assertSessionHasErrors('weekly_hour_limit');
});

test('user without permission cannot create professor', function () {
    $user = User::factory()->create();
    $user->assignRole('Profesor');

    $this->actingAs(User::factory()->create())
        ->post('/scheduling/professors', [
            'user_id'           => $user->id,
            'weekly_hour_limit' => 20,
        ])
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// update
// ---------------------------------------------------------------------------

test('admin can update professor weekly hour limit and active status', function () {
    $professor = Professor::factory()->create(['weekly_hour_limit' => 20, 'active' => true]);

    $this->actingAs(userWithProfessorPerm('professors.update'))
        ->patch("/scheduling/professors/{$professor->id}", [
            'weekly_hour_limit' => 30,
            'active'            => false,
        ])
        ->assertRedirect(route('scheduling.professors.index'));

    expect($professor->fresh()->weekly_hour_limit)->toBe(30);
    expect($professor->fresh()->active)->toBeFalse();
});

test('user without permission cannot update professor', function () {
    $professor = Professor::factory()->create();

    $this->actingAs(User::factory()->create())
        ->patch("/scheduling/professors/{$professor->id}", [
            'weekly_hour_limit' => 30,
            'active'            => true,
        ])
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// destroy
// ---------------------------------------------------------------------------

test('admin can delete a professor', function () {
    $professor = Professor::factory()->create();

    $this->actingAs(userWithProfessorPerm('professors.delete'))
        ->delete("/scheduling/professors/{$professor->id}")
        ->assertRedirect(route('scheduling.professors.index'));

    expect(Professor::find($professor->id))->toBeNull();
});

test('user without permission cannot delete professor', function () {
    $professor = Professor::factory()->create();

    $this->actingAs(User::factory()->create())
        ->delete("/scheduling/professors/{$professor->id}")
        ->assertForbidden();
});
