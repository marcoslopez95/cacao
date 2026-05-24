<?php

use App\Models\Country;
use App\Models\State;
use App\Models\User;
use App\Models\UserAddress;
use Database\Seeders\Catalogs\GeographicSeeder;
use Database\Seeders\Catalogs\UserProfileCatalogsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
    $this->seed(GeographicSeeder::class);
    $this->seed(UserProfileCatalogsSeeder::class);
});

function adminForAddress(): User
{
    $user = User::factory()->create();
    $user->assignRole('Administrador');

    return $user;
}

test('admin can create an address for a user', function () {
    $admin = adminForAddress();
    $target = User::factory()->create();
    $venezuela = Country::where('iso2', 'VE')->first();
    $state = State::where('country_id', $venezuela->id)->first();

    $response = $this->actingAs($admin)->postJson(route('security.users.addresses.store', $target), [
        'country_id' => $venezuela->id,
        'state_id' => $state->id,
        'address_line1' => 'Av. Principal 123',
        'is_primary' => true,
    ]);

    $response->assertSuccessful();
    expect(UserAddress::where('user_id', $target->id)->exists())->toBeTrue();
});

test('creating a primary address flips existing primary addresses to non-primary', function () {
    $admin = adminForAddress();
    $target = User::factory()->create();
    $venezuela = Country::where('iso2', 'VE')->first();
    $state = State::where('country_id', $venezuela->id)->first();

    // Create first address as primary
    $this->actingAs($admin)->postJson(route('security.users.addresses.store', $target), [
        'country_id' => $venezuela->id,
        'state_id' => $state->id,
        'address_line1' => 'Primera dirección',
        'is_primary' => true,
    ])->assertSuccessful();

    $firstAddress = UserAddress::where('user_id', $target->id)->first();
    expect($firstAddress->is_primary)->toBeTrue();

    // Create second address also as primary
    $this->actingAs($admin)->postJson(route('security.users.addresses.store', $target), [
        'country_id' => $venezuela->id,
        'state_id' => $state->id,
        'address_line1' => 'Segunda dirección',
        'is_primary' => true,
    ])->assertSuccessful();

    // First address must now be non-primary
    expect($firstAddress->fresh()->is_primary)->toBeFalse();
    expect(UserAddress::where('user_id', $target->id)->where('is_primary', true)->count())->toBe(1);
});

test('geographic chain validation rejects a state that does not belong to the given country', function () {
    $admin = adminForAddress();
    $target = User::factory()->create();

    $venezuela = Country::where('iso2', 'VE')->first();

    // Create a second country and a state belonging to it
    $otherCountry = Country::create([
        'iso2' => 'XX',
        'iso3' => 'XXX',
        'name' => 'País de Prueba',
        'active' => true,
    ]);

    $foreignState = State::create([
        'country_id' => $otherCountry->id,
        'code' => 'TS',
        'name' => 'Estado de Prueba',
        'active' => true,
    ]);

    $response = $this->actingAs($admin)->postJson(route('security.users.addresses.store', $target), [
        'country_id' => $venezuela->id,
        'state_id' => $foreignState->id,   // belongs to a different country
        'address_line1' => 'Dirección inválida',
        'is_primary' => false,
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['state_id']);
});
