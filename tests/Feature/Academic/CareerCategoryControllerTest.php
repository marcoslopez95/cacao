<?php

use App\Models\CareerCategory;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Permission::firstOrCreate(['name' => 'career-categories.view', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'career-categories.create', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'career-categories.update', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'career-categories.delete', 'guard_name' => 'web']);
});

function categoryUserWith(string $permission): User
{
    $user = User::factory()->create();
    $user->givePermissionTo($permission);

    return $user;
}

// ---------------------------------------------------------------------------
// index
// ---------------------------------------------------------------------------

test('unauthenticated user is redirected to login', function () {
    $this->get('/academic/career-categories')
        ->assertRedirect('/login');
});

test('authenticated user without career-categories.view gets 403', function () {
    $this->actingAs(User::factory()->create())
        ->get('/academic/career-categories')
        ->assertForbidden();
});

test('user with career-categories.view sees the index page', function () {
    CareerCategory::factory()->create(['name' => 'Ingeniería']);

    $this->actingAs(categoryUserWith('career-categories.view'))
        ->get('/academic/career-categories')
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('academic/CareerCategories/Index', false)
                ->has('categories', 1)
                ->has('can')
        );
});

// ---------------------------------------------------------------------------
// store
// ---------------------------------------------------------------------------

test('user without career-categories.create cannot store', function () {
    $this->actingAs(User::factory()->create())
        ->post('/academic/career-categories', ['name' => 'Humanidades'])
        ->assertForbidden();
});

test('store fails validation when name is blank', function () {
    $this->actingAs(categoryUserWith('career-categories.create'))
        ->post('/academic/career-categories', ['name' => ''])
        ->assertSessionHasErrors('name');
});

test('store fails validation when name is duplicate', function () {
    CareerCategory::factory()->create(['name' => 'Ingeniería']);

    $this->actingAs(categoryUserWith('career-categories.create'))
        ->post('/academic/career-categories', ['name' => 'Ingeniería'])
        ->assertSessionHasErrors('name');
});

test('store trims whitespace before uniqueness check', function () {
    CareerCategory::factory()->create(['name' => 'Ingeniería']);

    $this->actingAs(categoryUserWith('career-categories.create'))
        ->post('/academic/career-categories', ['name' => '  Ingeniería  '])
        ->assertSessionHasErrors('name');
});

test('user with career-categories.create can store a new category', function () {
    $this->actingAs(categoryUserWith('career-categories.create'))
        ->post('/academic/career-categories', ['name' => 'Humanidades'])
        ->assertRedirect(route('academic.career-categories.index'));

    expect(CareerCategory::where('name', 'Humanidades')->exists())->toBeTrue();
});

// ---------------------------------------------------------------------------
// update
// ---------------------------------------------------------------------------

test('user without career-categories.update cannot update', function () {
    $category = CareerCategory::factory()->create();

    $this->actingAs(User::factory()->create())
        ->patch("/academic/career-categories/{$category->id}", ['name' => 'Nuevo nombre'])
        ->assertForbidden();
});

test('update fails validation when name is duplicate of another category', function () {
    CareerCategory::factory()->create(['name' => 'Ingeniería']);
    $category = CareerCategory::factory()->create(['name' => 'Humanidades']);

    $this->actingAs(categoryUserWith('career-categories.update'))
        ->patch("/academic/career-categories/{$category->id}", ['name' => 'Ingeniería'])
        ->assertSessionHasErrors('name');
});

test('update allows same name for the same category', function () {
    $category = CareerCategory::factory()->create(['name' => 'Ingeniería']);

    $this->actingAs(categoryUserWith('career-categories.update'))
        ->patch("/academic/career-categories/{$category->id}", ['name' => 'Ingeniería'])
        ->assertRedirect(route('academic.career-categories.index'));
});

test('user with career-categories.update can rename a category', function () {
    $category = CareerCategory::factory()->create(['name' => 'Humanidades']);

    $this->actingAs(categoryUserWith('career-categories.update'))
        ->patch("/academic/career-categories/{$category->id}", ['name' => 'Ciencias Sociales'])
        ->assertRedirect(route('academic.career-categories.index'));

    expect($category->fresh()->name)->toBe('Ciencias Sociales');
});

// ---------------------------------------------------------------------------
// destroy
// ---------------------------------------------------------------------------

test('user without career-categories.delete cannot destroy', function () {
    $category = CareerCategory::factory()->create();

    $this->actingAs(User::factory()->create())
        ->delete("/academic/career-categories/{$category->id}")
        ->assertForbidden();
});

test('user with career-categories.delete can destroy a category', function () {
    $category = CareerCategory::factory()->create();

    $this->actingAs(categoryUserWith('career-categories.delete'))
        ->delete("/academic/career-categories/{$category->id}")
        ->assertRedirect(route('academic.career-categories.index'));

    expect(CareerCategory::find($category->id))->toBeNull();
});
