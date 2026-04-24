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

function userWithCoordPerm(string $permission): User
{
    $user = User::factory()->create();
    $user->givePermissionTo($permission);

    return $user;
}

// ---------------------------------------------------------------------------
// index
// ---------------------------------------------------------------------------

test('unauthenticated redirects to login', function () {
    $this->get('/security/coordinations')->assertRedirect('/login');
});

test('user without coordinations.view gets 403', function () {
    $this->actingAs(User::factory()->create())
        ->get('/security/coordinations')
        ->assertForbidden();
});

test('user with coordinations.view sees index', function () {
    $this->actingAs(userWithCoordPerm('coordinations.view'))
        ->get('/security/coordinations')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('security/Coordinations/Index', false)
            ->has('coordinations')
            ->has('coordinators')
        );
});

test('index includes current coordinator in row', function () {
    $actor = userWithCoordPerm('coordinations.view');
    $coordinator = User::factory()->create(['name' => 'Ana López']);
    $coordination = Coordination::factory()->create(['name' => 'Coord Test']);
    CoordinationAssignment::factory()->active()->create([
        'coordination_id' => $coordination->id,
        'user_id' => $coordinator->id,
        'assigned_by' => $actor->id,
        'assigned_at' => now(),
    ]);

    $this->actingAs($actor)
        ->get('/security/coordinations')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('coordinations.data.0.current_coordinator.name', 'Ana López')
        );
});

test('index filters by search', function () {
    Coordination::factory()->create(['name' => 'Coordinación de Sistemas']);
    Coordination::factory()->create(['name' => 'Coordinación de Física']);

    $this->actingAs(userWithCoordPerm('coordinations.view'))
        ->get('/security/coordinations?search=Sistemas')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('coordinations.total', 1));
});

test('index filters by type', function () {
    Coordination::factory()->career()->create();
    Coordination::factory()->grade()->create();

    $this->actingAs(userWithCoordPerm('coordinations.view'))
        ->get('/security/coordinations?type=grade')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('coordinations.total', 1));
});

test('index filters by education level', function () {
    Coordination::factory()->career()->create(); // university
    Coordination::factory()->grade()->create(); // secondary

    $this->actingAs(userWithCoordPerm('coordinations.view'))
        ->get('/security/coordinations?education_level=secondary')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('coordinations.total', 1));
});

test('index filters by active status', function () {
    Coordination::factory()->create(['active' => true]);
    Coordination::factory()->inactive()->create();

    $this->actingAs(userWithCoordPerm('coordinations.view'))
        ->get('/security/coordinations?status=inactive')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('coordinations.total', 1));
});

// ---------------------------------------------------------------------------
// store
// ---------------------------------------------------------------------------

test('user without coordinations.create gets 403 on store', function () {
    $this->actingAs(User::factory()->create())
        ->post('/security/coordinations', ['name' => 'X', 'type' => 'career', 'education_level' => 'university'])
        ->assertForbidden();
});

test('stores a career coordination', function () {
    $this->actingAs(userWithCoordPerm('coordinations.create'))
        ->post('/security/coordinations', [
            'name' => 'Coordinación de Ingeniería',
            'type' => 'career',
            'education_level' => 'university',
        ])
        ->assertRedirect('/security/coordinations');

    expect(Coordination::where('name', 'Coordinación de Ingeniería')->exists())->toBeTrue();
});

test('stores a grade coordination', function () {
    $this->actingAs(userWithCoordPerm('coordinations.create'))
        ->post('/security/coordinations', [
            'name' => '1er Año Media General',
            'type' => 'grade',
            'education_level' => 'secondary',
            'secondary_type' => 'media_general',
            'grade_year' => 1,
        ])
        ->assertRedirect('/security/coordinations');

    expect(Coordination::where('grade_year', 1)->exists())->toBeTrue();
});

test('rejects grade_year exceeding max for media_general', function () {
    $this->actingAs(userWithCoordPerm('coordinations.create'))
        ->post('/security/coordinations', [
            'name' => '6to Año',
            'type' => 'grade',
            'education_level' => 'secondary',
            'secondary_type' => 'media_general',
            'grade_year' => 6,
        ])
        ->assertSessionHasErrors('grade_year');
});

test('accepts grade_year 6 for bachillerato', function () {
    $this->actingAs(userWithCoordPerm('coordinations.create'))
        ->post('/security/coordinations', [
            'name' => '6to Año Bachillerato',
            'type' => 'grade',
            'education_level' => 'secondary',
            'secondary_type' => 'bachillerato',
            'grade_year' => 6,
        ])
        ->assertRedirect('/security/coordinations');
});

// ---------------------------------------------------------------------------
// update
// ---------------------------------------------------------------------------

test('user without coordinations.edit gets 403 on update', function () {
    $coordination = Coordination::factory()->create();

    $this->actingAs(User::factory()->create())
        ->patch("/security/coordinations/{$coordination->id}", ['name' => 'X'])
        ->assertForbidden();
});

test('updates a coordination', function () {
    $coordination = Coordination::factory()->create(['name' => 'Original']);

    $this->actingAs(userWithCoordPerm('coordinations.edit'))
        ->patch("/security/coordinations/{$coordination->id}", [
            'name' => 'Actualizada',
            'type' => 'career',
            'education_level' => 'university',
        ])
        ->assertRedirect('/security/coordinations');

    expect($coordination->fresh()->name)->toBe('Actualizada');
});

// ---------------------------------------------------------------------------
// destroy
// ---------------------------------------------------------------------------

test('user without coordinations.delete gets 403 on destroy', function () {
    $coordination = Coordination::factory()->create();

    $this->actingAs(User::factory()->create())
        ->delete("/security/coordinations/{$coordination->id}")
        ->assertForbidden();
});

test('deletes a coordination without active coordinator', function () {
    $coordination = Coordination::factory()->create();

    $this->actingAs(userWithCoordPerm('coordinations.delete'))
        ->delete("/security/coordinations/{$coordination->id}")
        ->assertRedirect('/security/coordinations');

    expect(Coordination::find($coordination->id))->toBeNull();
});

test('cannot delete coordination with active coordinator', function () {
    $actor = userWithCoordPerm('coordinations.delete');
    $coordination = Coordination::factory()->create();
    CoordinationAssignment::factory()->active()->create([
        'coordination_id' => $coordination->id,
        'assigned_by' => $actor->id,
        'assigned_at' => now(),
    ]);

    $this->actingAs($actor)
        ->delete("/security/coordinations/{$coordination->id}")
        ->assertRedirect('/security/coordinations');

    expect(Coordination::find($coordination->id))->not->toBeNull();
});
