<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach ([
        'users.view', 'users.create', 'users.update', 'users.delete',
        'users.deactivate', 'users.reset-password', 'users.invite',
    ] as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
    }

    Role::firstOrCreate(['name' => 'Profesor', 'guard_name' => 'web']);
});

function userWithUserPerm(string $permission): User
{
    $user = User::factory()->create();
    $user->givePermissionTo($permission);

    return $user;
}

// ---------------------------------------------------------------------------
// index
// ---------------------------------------------------------------------------

test('unauthenticated redirects to login', function () {
    $this->get('/security/users')->assertRedirect('/login');
});

test('user without users.view gets 403', function () {
    $this->actingAs(User::factory()->create())
        ->get('/security/users')
        ->assertForbidden();
});

test('user with users.view sees users index', function () {
    $this->actingAs(userWithUserPerm('users.view'))
        ->get('/security/users')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('security/Users/Index', false)
            ->has('users')
            ->has('roles')
        );
});

test('index filters by search', function () {
    $actor = User::factory()->create(['name' => 'Test Actor', 'email' => 'actor@test.com']);
    $actor->givePermissionTo('users.view');

    User::factory()->create(['name' => 'Ana García', 'email' => 'ana@test.com']);
    User::factory()->create(['name' => 'Pedro López', 'email' => 'pedro@test.com']);

    $this->actingAs($actor)
        ->get('/security/users?search=Ana')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('users.total', 1));
});

test('index filters by status active only returns active users', function () {
    User::factory()->create(['active' => true]);
    User::factory()->inactive()->create();

    $actor = userWithUserPerm('users.view');

    $this->actingAs($actor)
        ->get('/security/users?status=active')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('users.total', 2)); // actor + 1 active user
});

// ---------------------------------------------------------------------------
// store
// ---------------------------------------------------------------------------

test('user without users.create gets 403 on store', function () {
    $this->actingAs(User::factory()->create())
        ->post('/security/users', [])
        ->assertForbidden();
});

test('store with password_mode link creates user and sends reset', function () {
    Password::shouldReceive('sendResetLink')->once()->andReturn(Password::RESET_LINK_SENT);

    $actor = userWithUserPerm('users.create');

    $this->actingAs($actor)
        ->post('/security/users', [
            'name' => 'Carlos Pérez',
            'email' => 'carlos@test.com',
            'role' => 'Profesor',
            'password_mode' => 'link',
        ])
        ->assertRedirect(route('security.users.index'));

    expect(User::where('email', 'carlos@test.com')->exists())->toBeTrue();
});

test('store with password_mode manual creates user with given password', function () {
    $actor = userWithUserPerm('users.create');

    $this->actingAs($actor)
        ->post('/security/users', [
            'name' => 'Laura Torres',
            'email' => 'laura@test.com',
            'role' => 'Profesor',
            'password_mode' => 'manual',
            'password' => 'secret12345',
            'password_confirmation' => 'secret12345',
        ])
        ->assertRedirect(route('security.users.index'));

    $user = User::where('email', 'laura@test.com')->firstOrFail();
    expect(Hash::check('secret12345', $user->password))->toBeTrue();
});

test('store with password_mode random creates user', function () {
    $actor = userWithUserPerm('users.create');

    $this->actingAs($actor)
        ->post('/security/users', [
            'name' => 'Marta Díaz',
            'email' => 'marta@test.com',
            'role' => 'Profesor',
            'password_mode' => 'random',
        ])
        ->assertRedirect(route('security.users.index'));

    expect(User::where('email', 'marta@test.com')->exists())->toBeTrue();
});

// ---------------------------------------------------------------------------
// update
// ---------------------------------------------------------------------------

test('update changes name, email and roles', function () {
    $actor = userWithUserPerm('users.update');
    $target = User::factory()->create();

    $this->actingAs($actor)
        ->patch("/security/users/{$target->id}", [
            'name' => 'Nombre Nuevo',
            'email' => 'nuevo@test.com',
            'roles' => ['Profesor'],
        ])
        ->assertRedirect(route('security.users.index'));

    $target->refresh();
    expect($target->name)->toBe('Nombre Nuevo')
        ->and($target->email)->toBe('nuevo@test.com')
        ->and($target->hasRole('Profesor'))->toBeTrue();
});

test('user cannot update self via this endpoint', function () {
    $actor = userWithUserPerm('users.update');

    $this->actingAs($actor)
        ->patch("/security/users/{$actor->id}", [
            'name' => 'Self Edit',
            'email' => $actor->email,
            'roles' => [],
        ])
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// destroy
// ---------------------------------------------------------------------------

test('user without users.delete gets 403 on destroy', function () {
    $target = User::factory()->create();

    $this->actingAs(User::factory()->create())
        ->delete("/security/users/{$target->id}")
        ->assertForbidden();
});

test('destroy deletes user with no history', function () {
    $actor = userWithUserPerm('users.delete');
    $target = User::factory()->create();

    $this->actingAs($actor)
        ->delete("/security/users/{$target->id}")
        ->assertRedirect(route('security.users.index'));

    expect(User::find($target->id))->toBeNull();
});

// ---------------------------------------------------------------------------
// deactivate
// ---------------------------------------------------------------------------

test('deactivate toggles active state', function () {
    $actor = userWithUserPerm('users.deactivate');
    $target = User::factory()->create(['active' => true]);

    $this->actingAs($actor)
        ->patch("/security/users/{$target->id}/deactivate")
        ->assertRedirect(route('security.users.index'));

    expect($target->fresh()->active)->toBeFalse();
});

test('user cannot deactivate self', function () {
    $actor = userWithUserPerm('users.deactivate');

    $this->actingAs($actor)
        ->patch("/security/users/{$actor->id}/deactivate")
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// resetPassword
// ---------------------------------------------------------------------------

test('resetPassword with link mode sends reset email', function () {
    Password::shouldReceive('sendResetLink')->once()->andReturn(Password::RESET_LINK_SENT);

    $actor = userWithUserPerm('users.reset-password');
    $target = User::factory()->create();

    $this->actingAs($actor)
        ->post("/security/users/{$target->id}/reset-password", [
            'password_mode' => 'link',
        ])
        ->assertRedirect(route('security.users.index'));
});

test('resetPassword with manual mode updates password', function () {
    $actor = userWithUserPerm('users.reset-password');
    $target = User::factory()->create();

    $this->actingAs($actor)
        ->post("/security/users/{$target->id}/reset-password", [
            'password_mode' => 'manual',
            'password' => 'newpassword1',
            'password_confirmation' => 'newpassword1',
        ])
        ->assertRedirect(route('security.users.index'));

    expect(Hash::check('newpassword1', $target->fresh()->password))->toBeTrue();
});

test('resetPassword with random mode updates password', function () {
    $actor = userWithUserPerm('users.reset-password');
    $target = User::factory()->create();
    $oldPassword = $target->password;

    $this->actingAs($actor)
        ->post("/security/users/{$target->id}/reset-password", [
            'password_mode' => 'random',
        ])
        ->assertRedirect(route('security.users.index'));

    expect($target->fresh()->password)->not->toBe($oldPassword);
});
