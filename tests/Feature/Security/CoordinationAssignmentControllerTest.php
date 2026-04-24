<?php

use App\Models\Coordination;
use App\Models\CoordinationAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach ([
        'coordinations.view', 'coordinations.create', 'coordinations.edit',
        'coordinations.delete', 'coordinations.assign', 'coordinations.view_history',
    ] as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
    }

    Role::firstOrCreate(['name' => 'Coordinador de Area', 'guard_name' => 'web']);
});

function actorWithAssignPerm(): User
{
    $user = User::factory()->create();
    $user->givePermissionTo('coordinations.assign');

    return $user;
}

function coordinatorUser(): User
{
    $user = User::factory()->create(['active' => true]);
    $user->assignRole('Coordinador de Area');

    return $user;
}

// ---------------------------------------------------------------------------
// store (assign coordinator)
// ---------------------------------------------------------------------------

test('unauthenticated cannot assign coordinator', function () {
    $coordination = Coordination::factory()->create();
    $coordinator = coordinatorUser();

    $this->post("/security/coordinations/{$coordination->id}/assignments", ['user_id' => $coordinator->id])
        ->assertRedirect('/login');
});

test('user without coordinations.assign gets 403', function () {
    $coordination = Coordination::factory()->create();
    $coordinator = coordinatorUser();

    $this->actingAs(User::factory()->create())
        ->post("/security/coordinations/{$coordination->id}/assignments", ['user_id' => $coordinator->id])
        ->assertForbidden();
});

test('assigns coordinator to coordination with no prior assignment', function () {
    $actor = actorWithAssignPerm();
    $coordination = Coordination::factory()->create();
    $coordinator = coordinatorUser();

    $this->actingAs($actor)
        ->post("/security/coordinations/{$coordination->id}/assignments", ['user_id' => $coordinator->id])
        ->assertRedirect('/security/coordinations');

    $assignment = CoordinationAssignment::where('coordination_id', $coordination->id)->first();
    expect($assignment)->not->toBeNull();
    expect($assignment->user_id)->toBe($coordinator->id);
    expect($assignment->ended_at)->toBeNull();
});

test('closes previous assignment when reassigning', function () {
    $actor = actorWithAssignPerm();
    $coordination = Coordination::factory()->create();
    $firstCoordinator = coordinatorUser();
    $secondCoordinator = coordinatorUser();

    // First assignment
    CoordinationAssignment::factory()->active()->create([
        'coordination_id' => $coordination->id,
        'user_id' => $firstCoordinator->id,
        'assigned_by' => $actor->id,
        'assigned_at' => now()->subDay(),
    ]);

    // Reassign
    $this->actingAs($actor)
        ->post("/security/coordinations/{$coordination->id}/assignments", ['user_id' => $secondCoordinator->id])
        ->assertRedirect('/security/coordinations');

    expect(
        CoordinationAssignment::where('coordination_id', $coordination->id)
            ->whereNull('ended_at')
            ->count()
    )->toBe(1);

    expect(
        CoordinationAssignment::where('coordination_id', $coordination->id)
            ->whereNotNull('ended_at')
            ->count()
    )->toBe(1);

    expect(
        CoordinationAssignment::where('coordination_id', $coordination->id)
            ->whereNull('ended_at')
            ->first()->user_id
    )->toBe($secondCoordinator->id);
});

test('rejects user without Coordinator role', function () {
    $actor = actorWithAssignPerm();
    $coordination = Coordination::factory()->create();
    $nonCoordinator = User::factory()->create(); // no coordinator role

    $this->actingAs($actor)
        ->post("/security/coordinations/{$coordination->id}/assignments", ['user_id' => $nonCoordinator->id])
        ->assertSessionHasErrors('user_id');
});

// ---------------------------------------------------------------------------
// index (history)
// ---------------------------------------------------------------------------

test('returns assignment history as JSON', function () {
    $actor = User::factory()->create();
    $actor->givePermissionTo('coordinations.view_history');
    $coordination = Coordination::factory()->create();
    $coordinator = coordinatorUser();

    CoordinationAssignment::factory()->closed()->create([
        'coordination_id' => $coordination->id,
        'user_id' => $coordinator->id,
        'assigned_by' => $actor->id,
        'assigned_at' => now()->subMonth(),
    ]);
    CoordinationAssignment::factory()->active()->create([
        'coordination_id' => $coordination->id,
        'user_id' => $coordinator->id,
        'assigned_by' => $actor->id,
        'assigned_at' => now(),
    ]);

    $this->actingAs($actor)
        ->getJson("/security/coordinations/{$coordination->id}/assignments")
        ->assertOk()
        ->assertJsonCount(2)
        ->assertJsonStructure([['id', 'user', 'assigned_by', 'assigned_at', 'ended_at']]);
});

test('user without coordinations.view_history gets 403 on history', function () {
    $coordination = Coordination::factory()->create();

    $this->actingAs(User::factory()->create())
        ->getJson("/security/coordinations/{$coordination->id}/assignments")
        ->assertForbidden();
});
