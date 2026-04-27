<?php

use App\Models\Building;
use App\Models\Classroom;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach ([
        'classrooms.view',
        'classrooms.create',
        'classrooms.update',
        'classrooms.delete',
        'buildings.view',
    ] as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
    }
});

function userWithClassroomPerm(string $permission): User
{
    $user = User::factory()->create();
    $user->givePermissionTo($permission);

    return $user;
}

// ---------------------------------------------------------------------------
// index
// ---------------------------------------------------------------------------

test('admin can list classrooms', function () {
    Classroom::factory()->count(3)->create();

    $this->actingAs(userWithClassroomPerm('classrooms.view'))
        ->get('/infrastructure/classrooms')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('infrastructure/Classrooms/Index', false)
            ->has('classrooms', 3)
        );
});

test('unauthenticated user cannot list classrooms', function () {
    $this->get('/infrastructure/classrooms')
        ->assertRedirect('/login');
});

test('user without permission cannot list classrooms', function () {
    $this->actingAs(User::factory()->create())
        ->get('/infrastructure/classrooms')
        ->assertForbidden();
});

test('admin can filter classrooms by building', function () {
    $buildingA = Building::factory()->create();
    $buildingB = Building::factory()->create();

    Classroom::factory()->count(2)->create(['building_id' => $buildingA->id]);
    Classroom::factory()->count(3)->create(['building_id' => $buildingB->id]);

    $this->actingAs(userWithClassroomPerm('classrooms.view'))
        ->get("/infrastructure/classrooms?building_id={$buildingA->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('classrooms', 2));
});

// ---------------------------------------------------------------------------
// store
// ---------------------------------------------------------------------------

test('admin can create classroom', function () {
    $building = Building::factory()->create();

    $this->actingAs(userWithClassroomPerm('classrooms.create'))
        ->post('/infrastructure/classrooms', [
            'building_id' => $building->id,
            'identifier'  => 'A-101',
            'type'        => 'theory',
            'capacity'    => 30,
        ])
        ->assertRedirect(route('infrastructure.classrooms.index'));

    expect(Classroom::where('identifier', 'A-101')->where('building_id', $building->id)->exists())->toBeTrue();
});

test('admin cannot create classroom with duplicate identifier in same building', function () {
    $building = Building::factory()->create();
    Classroom::factory()->create(['building_id' => $building->id, 'identifier' => 'B-201']);

    $this->actingAs(userWithClassroomPerm('classrooms.create'))
        ->post('/infrastructure/classrooms', [
            'building_id' => $building->id,
            'identifier'  => 'B-201',
            'type'        => 'laboratory',
            'capacity'    => 20,
        ])
        ->assertSessionHasErrors('identifier');

    expect(Classroom::where('building_id', $building->id)->where('identifier', 'B-201')->count())->toBe(1);
});

test('admin can create classroom with same identifier in different building', function () {
    $buildingA = Building::factory()->create();
    $buildingB = Building::factory()->create();
    Classroom::factory()->create(['building_id' => $buildingA->id, 'identifier' => 'C-301']);

    $this->actingAs(userWithClassroomPerm('classrooms.create'))
        ->post('/infrastructure/classrooms', [
            'building_id' => $buildingB->id,
            'identifier'  => 'C-301',
            'type'        => 'theory',
            'capacity'    => 40,
        ])
        ->assertRedirect(route('infrastructure.classrooms.index'));

    expect(Classroom::where('identifier', 'C-301')->count())->toBe(2);
});

// ---------------------------------------------------------------------------
// update
// ---------------------------------------------------------------------------

test('admin can update classroom', function () {
    $classroom = Classroom::factory()->theory()->create(['capacity' => 25]);

    $this->actingAs(userWithClassroomPerm('classrooms.update'))
        ->patch("/infrastructure/classrooms/{$classroom->id}", [
            'building_id' => $classroom->building_id,
            'identifier'  => $classroom->identifier,
            'type'        => 'laboratory',
            'capacity'    => 15,
        ])
        ->assertRedirect(route('infrastructure.classrooms.index'));

    expect($classroom->fresh()->capacity)->toBe(15)
        ->and($classroom->fresh()->type->value)->toBe('laboratory');
});

// ---------------------------------------------------------------------------
// destroy
// ---------------------------------------------------------------------------

test('admin can delete classroom', function () {
    $classroom = Classroom::factory()->create();

    $this->actingAs(userWithClassroomPerm('classrooms.delete'))
        ->delete("/infrastructure/classrooms/{$classroom->id}")
        ->assertRedirect(route('infrastructure.classrooms.index'));

    expect(Classroom::find($classroom->id))->toBeNull();
});
