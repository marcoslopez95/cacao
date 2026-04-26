<?php

use App\Models\Career;
use App\Models\CareerCategory;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Permission::firstOrCreate(['name' => 'careers.view',   'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'careers.create', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'careers.update', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'careers.delete', 'guard_name' => 'web']);

    Permission::firstOrCreate(['name' => 'career-categories.view', 'guard_name' => 'web']);
});

function careerUserWith(string $permission): User
{
    $user = User::factory()->create();
    $user->givePermissionTo($permission);

    return $user;
}

// ---------------------------------------------------------------------------
// index
// ---------------------------------------------------------------------------

test('unauthenticated user is redirected to login on careers index', function () {
    $this->get('/academic/careers')
        ->assertRedirect('/login');
});

test('authenticated user without careers.view gets 403 on careers index', function () {
    $this->actingAs(User::factory()->create())
        ->get('/academic/careers')
        ->assertForbidden();
});

test('user with careers.view sees the careers index page', function () {
    $category = CareerCategory::factory()->create();
    Career::factory()->create(['career_category_id' => $category->id, 'name' => 'Informática', 'code' => 'INF']);

    $this->actingAs(careerUserWith('careers.view'))
        ->get('/academic/careers')
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('academic/Careers/Index', false)
                ->has('careers', 1)
                ->has('categories')
                ->has('can')
        );
});

test('careers index includes category name in each career', function () {
    $category = CareerCategory::factory()->create(['name' => 'Ingeniería']);
    Career::factory()->create(['career_category_id' => $category->id, 'code' => 'INF']);

    $this->actingAs(careerUserWith('careers.view'))
        ->get('/academic/careers')
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->has('careers.0.category.name')
                ->where('careers.0.category.name', 'Ingeniería')
        );
});

// ---------------------------------------------------------------------------
// store
// ---------------------------------------------------------------------------

test('user without careers.create cannot store a career', function () {
    $category = CareerCategory::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post('/academic/careers', [
            'career_category_id' => $category->id,
            'name' => 'Sistemas',
        ])
        ->assertForbidden();
});

test('store fails validation when name is blank', function () {
    $category = CareerCategory::factory()->create();

    $this->actingAs(careerUserWith('careers.create'))
        ->post('/academic/careers', [
            'career_category_id' => $category->id,
            'name' => '',
        ])
        ->assertSessionHasErrors('name');
});

test('store fails validation when category does not exist', function () {
    $this->actingAs(careerUserWith('careers.create'))
        ->post('/academic/careers', [
            'career_category_id' => 9999,
            'name' => 'Sistemas',
        ])
        ->assertSessionHasErrors('career_category_id');
});

test('user with careers.create can store a new career', function () {
    $category = CareerCategory::factory()->create();

    $this->actingAs(careerUserWith('careers.create'))
        ->post('/academic/careers', [
            'career_category_id' => $category->id,
            'name' => 'Informática',
        ])
        ->assertRedirect(route('academic.careers.index'));

    $career = Career::where('name', 'Informática')->first();
    expect($career)->not->toBeNull()
        ->and($career->code)->not->toBeNull()
        ->and($career->active)->toBeTrue();
});

test('store auto-generates code from name skipping stop words', function () {
    $category = CareerCategory::factory()->create();

    $this->actingAs(careerUserWith('careers.create'))
        ->post('/academic/careers', [
            'career_category_id' => $category->id,
            'name' => 'Ingeniería en Sistemas',
        ])
        ->assertRedirect(route('academic.careers.index'));

    $career = Career::where('name', 'Ingeniería en Sistemas')->first();
    expect($career)->not->toBeNull()
        ->and($career->code)->toMatch('/^IS-\d{2,}$/');
});

test('store auto-generates code using only significant words', function () {
    $category = CareerCategory::factory()->create();

    $this->actingAs(careerUserWith('careers.create'))
        ->post('/academic/careers', [
            'career_category_id' => $category->id,
            'name' => 'Diseño Gráfico y Comunicación Visual',
        ])
        ->assertRedirect(route('academic.careers.index'));

    $career = Career::where('name', 'Diseño Gráfico y Comunicación Visual')->first();
    expect($career)->not->toBeNull()
        ->and($career->code)->toMatch('/^DGCV-\d{2,}$/');
});

// ---------------------------------------------------------------------------
// update
// ---------------------------------------------------------------------------

test('user without careers.update cannot update a career', function () {
    $career = Career::factory()->create();

    $this->actingAs(User::factory()->create())
        ->patch("/academic/careers/{$career->id}", [
            'career_category_id' => $career->career_category_id,
            'name' => 'Nuevo nombre',
            'code' => $career->code,
            'active' => true,
        ])
        ->assertForbidden();
});

test('update fails validation when code is duplicate of another career', function () {
    $category = CareerCategory::factory()->create();
    Career::factory()->create(['career_category_id' => $category->id, 'code' => 'INF']);
    $career = Career::factory()->create(['career_category_id' => $category->id, 'code' => 'SIS']);

    $this->actingAs(careerUserWith('careers.update'))
        ->patch("/academic/careers/{$career->id}", [
            'career_category_id' => $category->id,
            'name' => $career->name,
            'code' => 'INF',
            'active' => true,
        ])
        ->assertSessionHasErrors('code');
});

test('update allows same code for the same career', function () {
    $career = Career::factory()->create(['code' => 'INF']);

    $this->actingAs(careerUserWith('careers.update'))
        ->patch("/academic/careers/{$career->id}", [
            'career_category_id' => $career->career_category_id,
            'name' => $career->name,
            'code' => 'INF',
            'active' => true,
        ])
        ->assertRedirect(route('academic.careers.index'));
});

test('user with careers.update can rename a career', function () {
    $career = Career::factory()->create(['name' => 'Informática', 'code' => 'INF']);

    $this->actingAs(careerUserWith('careers.update'))
        ->patch("/academic/careers/{$career->id}", [
            'career_category_id' => $career->career_category_id,
            'name' => 'Ingeniería en Sistemas',
            'code' => 'INF',
            'active' => true,
        ])
        ->assertRedirect(route('academic.careers.index'));

    expect($career->fresh()->name)->toBe('Ingeniería en Sistemas');
});

test('user with careers.update can deactivate a career', function () {
    $career = Career::factory()->create(['active' => true]);

    $this->actingAs(careerUserWith('careers.update'))
        ->patch("/academic/careers/{$career->id}", [
            'career_category_id' => $career->career_category_id,
            'name' => $career->name,
            'code' => $career->code,
            'active' => false,
        ])
        ->assertRedirect(route('academic.careers.index'));

    expect($career->fresh()->active)->toBeFalse();
});

// ---------------------------------------------------------------------------
// destroy
// ---------------------------------------------------------------------------

test('user without careers.delete cannot destroy a career', function () {
    $career = Career::factory()->create();

    $this->actingAs(User::factory()->create())
        ->delete("/academic/careers/{$career->id}")
        ->assertForbidden();
});

test('user with careers.delete can destroy a career with no pensums', function () {
    $career = Career::factory()->create();

    $this->actingAs(careerUserWith('careers.delete'))
        ->delete("/academic/careers/{$career->id}")
        ->assertRedirect(route('academic.careers.index'));

    expect(Career::find($career->id))->toBeNull();
});
