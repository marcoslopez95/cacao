<?php

use App\Models\Building;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach ([
        'buildings.view',
        'buildings.create',
        'buildings.update',
        'buildings.delete',
    ] as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
    }
});

function userWithBuildingPerm(string $permission): User
{
    $user = User::factory()->create();
    $user->givePermissionTo($permission);

    return $user;
}

// ---------------------------------------------------------------------------
// index
// ---------------------------------------------------------------------------

test('admin can list buildings', function () {
    Building::factory()->count(3)->create();

    $this->actingAs(userWithBuildingPerm('buildings.view'))
        ->get('/infrastructure/buildings')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('infrastructure/Buildings/Index', false)
            ->has('buildings', 3)
        );
});

test('unauthenticated user cannot list buildings', function () {
    $this->get('/infrastructure/buildings')
        ->assertRedirect('/login');
});

test('user without permission cannot list buildings', function () {
    $this->actingAs(User::factory()->create())
        ->get('/infrastructure/buildings')
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// store
// ---------------------------------------------------------------------------

test('admin can create building', function () {
    $this->actingAs(userWithBuildingPerm('buildings.create'))
        ->post('/infrastructure/buildings', ['name' => 'Edificio Central'])
        ->assertRedirect(route('infrastructure.buildings.index'));

    expect(Building::where('name', 'Edificio Central')->exists())->toBeTrue();
});

test('admin cannot create building with duplicate name', function () {
    Building::factory()->create(['name' => 'Edificio A']);

    $this->actingAs(userWithBuildingPerm('buildings.create'))
        ->post('/infrastructure/buildings', ['name' => 'Edificio A'])
        ->assertSessionHasErrors('name');

    expect(Building::where('name', 'Edificio A')->count())->toBe(1);
});

// ---------------------------------------------------------------------------
// update
// ---------------------------------------------------------------------------

test('admin can update building', function () {
    $building = Building::factory()->create(['name' => 'Edificio Viejo']);

    $this->actingAs(userWithBuildingPerm('buildings.update'))
        ->patch("/infrastructure/buildings/{$building->id}", ['name' => 'Edificio Nuevo'])
        ->assertRedirect(route('infrastructure.buildings.index'));

    expect($building->fresh()->name)->toBe('Edificio Nuevo');
});

// ---------------------------------------------------------------------------
// destroy
// ---------------------------------------------------------------------------

test('admin can delete building', function () {
    $building = Building::factory()->create();

    $this->actingAs(userWithBuildingPerm('buildings.delete'))
        ->delete("/infrastructure/buildings/{$building->id}")
        ->assertRedirect(route('infrastructure.buildings.index'));

    expect(Building::find($building->id))->toBeNull();
});

test('admin cannot delete building that has classrooms', function () {
    Permission::firstOrCreate(['name' => 'classrooms.view', 'guard_name' => 'web']);

    $building = Building::factory()->create();
    Classroom::factory()->create(['building_id' => $building->id]);

    $this->actingAs(userWithBuildingPerm('buildings.delete'))
        ->delete("/infrastructure/buildings/{$building->id}")
        ->assertRedirect(route('infrastructure.buildings.index'));

    expect(Building::find($building->id))->not->toBeNull();
});
