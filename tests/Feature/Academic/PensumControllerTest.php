<?php

use App\Models\Career;
use App\Models\Pensum;
use App\Models\Subject;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Permission::firstOrCreate(['name' => 'pensums.view',   'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'pensums.create', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'pensums.update', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'pensums.delete', 'guard_name' => 'web']);
});

function pensumUserWith(string $permission): User
{
    $user = User::factory()->create();
    $user->givePermissionTo($permission);

    return $user;
}

// ---------------------------------------------------------------------------
// index
// ---------------------------------------------------------------------------

test('unauthenticated user is redirected to login on pensums index', function () {
    $career = Career::factory()->create();

    $this->get("/academic/careers/{$career->id}/pensums")
        ->assertRedirect('/login');
});

test('authenticated user without pensums.view gets 403 on pensums index', function () {
    $career = Career::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get("/academic/careers/{$career->id}/pensums")
        ->assertForbidden();
});

test('user with pensums.view sees the pensums index page for a career', function () {
    $career = Career::factory()->create();
    Pensum::factory()->create(['career_id' => $career->id]);

    $this->actingAs(pensumUserWith('pensums.view'))
        ->get("/academic/careers/{$career->id}/pensums")
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('academic/Pensums/Index', false)
                ->has('career')
                ->has('pensums', 1)
                ->has('can')
        );
});

test('pensums index only shows pensums belonging to the given career', function () {
    $career = Career::factory()->create();
    $otherCareer = Career::factory()->create();

    Pensum::factory()->create(['career_id' => $career->id]);
    Pensum::factory()->create(['career_id' => $otherCareer->id]);

    $this->actingAs(pensumUserWith('pensums.view'))
        ->get("/academic/careers/{$career->id}/pensums")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('pensums', 1));
});

// ---------------------------------------------------------------------------
// store
// ---------------------------------------------------------------------------

test('user without pensums.create cannot store a pensum', function () {
    $career = Career::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post("/academic/careers/{$career->id}/pensums", [
            'name' => 'Plan de Estudios 2020',
            'period_type' => 'semester',
            'total_periods' => 10,
        ])
        ->assertForbidden();
});

test('store fails validation when name is blank', function () {
    $career = Career::factory()->create();

    $this->actingAs(pensumUserWith('pensums.create'))
        ->post("/academic/careers/{$career->id}/pensums", [
            'name' => '',
            'period_type' => 'semester',
            'total_periods' => 10,
        ])
        ->assertSessionHasErrors('name');
});

test('store fails validation when period_type is invalid', function () {
    $career = Career::factory()->create();

    $this->actingAs(pensumUserWith('pensums.create'))
        ->post("/academic/careers/{$career->id}/pensums", [
            'name' => 'Plan de Estudios 2020',
            'period_type' => 'quarterly',
            'total_periods' => 10,
        ])
        ->assertSessionHasErrors('period_type');
});

test('store fails validation when total_periods is zero', function () {
    $career = Career::factory()->create();

    $this->actingAs(pensumUserWith('pensums.create'))
        ->post("/academic/careers/{$career->id}/pensums", [
            'name' => 'Plan de Estudios 2020',
            'period_type' => 'semester',
            'total_periods' => 0,
        ])
        ->assertSessionHasErrors('total_periods');
});

test('store fails validation when total_periods exceeds 20', function () {
    $career = Career::factory()->create();

    $this->actingAs(pensumUserWith('pensums.create'))
        ->post("/academic/careers/{$career->id}/pensums", [
            'name' => 'Plan de Estudios 2020',
            'period_type' => 'semester',
            'total_periods' => 21,
        ])
        ->assertSessionHasErrors('total_periods');
});

test('user with pensums.create can store a new pensum', function () {
    $career = Career::factory()->create();

    $this->actingAs(pensumUserWith('pensums.create'))
        ->post("/academic/careers/{$career->id}/pensums", [
            'name' => 'Plan de Estudios 2020',
            'period_type' => 'semester',
            'total_periods' => 10,
        ])
        ->assertRedirect(route('academic.pensums.index', $career));

    $pensum = Pensum::where('name', 'Plan de Estudios 2020')->first();
    expect($pensum)->not->toBeNull()
        ->and($pensum->career_id)->toBe($career->id)
        ->and($pensum->period_type)->toBe('semester')
        ->and($pensum->total_periods)->toBe(10)
        ->and($pensum->is_active)->toBeTrue();
});

// ---------------------------------------------------------------------------
// update
// ---------------------------------------------------------------------------

test('user without pensums.update cannot update a pensum', function () {
    $career = Career::factory()->create();
    $pensum = Pensum::factory()->create(['career_id' => $career->id]);

    $this->actingAs(User::factory()->create())
        ->patch("/academic/careers/{$career->id}/pensums/{$pensum->id}", [
            'name' => 'Plan Actualizado',
            'period_type' => 'semester',
            'total_periods' => 10,
            'is_active' => true,
        ])
        ->assertForbidden();
});

test('user with pensums.update can update a pensum', function () {
    $career = Career::factory()->create();
    $pensum = Pensum::factory()->create(['career_id' => $career->id, 'is_active' => true]);

    $this->actingAs(pensumUserWith('pensums.update'))
        ->patch("/academic/careers/{$career->id}/pensums/{$pensum->id}", [
            'name' => 'Plan Actualizado',
            'period_type' => 'year',
            'total_periods' => 5,
            'is_active' => false,
        ])
        ->assertRedirect(route('academic.pensums.index', $career));

    $updated = $pensum->fresh();
    expect($updated->name)->toBe('Plan Actualizado')
        ->and($updated->period_type)->toBe('year')
        ->and($updated->total_periods)->toBe(5)
        ->and($updated->is_active)->toBeFalse();
});

test('user with pensums.update can toggle is_active to false', function () {
    $career = Career::factory()->create();
    $pensum = Pensum::factory()->create(['career_id' => $career->id, 'is_active' => true]);

    $this->actingAs(pensumUserWith('pensums.update'))
        ->patch("/academic/careers/{$career->id}/pensums/{$pensum->id}", [
            'name' => $pensum->name,
            'period_type' => $pensum->period_type,
            'total_periods' => $pensum->total_periods,
            'is_active' => false,
        ])
        ->assertRedirect(route('academic.pensums.index', $career));

    expect($pensum->fresh()->is_active)->toBeFalse();
});

// ---------------------------------------------------------------------------
// destroy
// ---------------------------------------------------------------------------

test('user without pensums.delete cannot destroy a pensum', function () {
    $career = Career::factory()->create();
    $pensum = Pensum::factory()->create(['career_id' => $career->id]);

    $this->actingAs(User::factory()->create())
        ->delete("/academic/careers/{$career->id}/pensums/{$pensum->id}")
        ->assertForbidden();
});

test('user with pensums.delete can destroy a pensum', function () {
    $career = Career::factory()->create();
    $pensum = Pensum::factory()->create(['career_id' => $career->id]);

    $this->actingAs(pensumUserWith('pensums.delete'))
        ->delete("/academic/careers/{$career->id}/pensums/{$pensum->id}")
        ->assertRedirect(route('academic.pensums.index', $career));

    expect(Pensum::find($pensum->id))->toBeNull();
});

test('pensum belonging to another career returns 404 on update', function () {
    $career = Career::factory()->create();
    $otherCareer = Career::factory()->create();
    $pensum = Pensum::factory()->create(['career_id' => $otherCareer->id]);

    $this->actingAs(pensumUserWith('pensums.update'))
        ->patch("/academic/careers/{$career->id}/pensums/{$pensum->id}", [
            'name' => 'Plan Actualizado',
            'period_type' => 'semester',
            'total_periods' => 10,
            'is_active' => true,
        ])
        ->assertNotFound();
});

test('pensum belonging to another career returns 404 on destroy', function () {
    $career = Career::factory()->create();
    $otherCareer = Career::factory()->create();
    $pensum = Pensum::factory()->create(['career_id' => $otherCareer->id]);

    $this->actingAs(pensumUserWith('pensums.delete'))
        ->delete("/academic/careers/{$career->id}/pensums/{$pensum->id}")
        ->assertNotFound();

    expect(Pensum::find($pensum->id))->not->toBeNull();
});

test('deleting a pensum with subjects is blocked and pensum persists', function () {
    $career = Career::factory()->create();
    $pensum = Pensum::factory()->create(['career_id' => $career->id]);
    Subject::factory()->create(['pensum_id' => $pensum->id, 'period_number' => 1]);

    $this->actingAs(pensumUserWith('pensums.delete'))
        ->delete("/academic/careers/{$career->id}/pensums/{$pensum->id}")
        ->assertRedirect(route('academic.pensums.index', $career));

    expect(Pensum::find($pensum->id))->not->toBeNull();
});
