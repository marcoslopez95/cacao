<?php

use App\Models\User;
use App\Models\UserConsent;
use App\Services\ConsentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
});

/**
 * Returns an admin User with the Administrador role.
 */
function adminForConsent(): User
{
    $user = User::factory()->create();
    $user->assignRole('Administrador');

    return $user;
}

/**
 * Returns a plain User without any role.
 */
function plainUserForConsent(): User
{
    return User::factory()->create();
}

/**
 * Valid consent payload.
 *
 * @return array<string, mixed>
 */
function validConsentPayload(): array
{
    return [
        'policy_version' => 'v1.0',
        'accepts_data_processing' => true,
        'accepts_image_use' => true,
        'accepts_whatsapp_contact' => false,
        'accepts_email_contact' => true,
    ];
}

/**
 * Creates an active consent record for the given user.
 */
function createActiveConsent(User $user): UserConsent
{
    return UserConsent::create([
        'user_id' => $user->id,
        'policy_version' => 'v1.0',
        'accepts_data_processing' => true,
        'accepts_image_use' => true,
        'accepts_whatsapp_contact' => true,
        'accepts_email_contact' => true,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'test',
        'granted_at' => now(),
        'revoked_at' => null,
    ]);
}

test('user can create consent for themselves', function () {
    $user = plainUserForConsent();

    $response = $this->actingAs($user)->postJson(
        route('security.users.consents.store', $user),
        validConsentPayload()
    );

    $response->assertSuccessful();
    expect(UserConsent::where('user_id', $user->id)->exists())->toBeTrue();
});

test('active consent check returns true after creation', function () {
    $user = plainUserForConsent();
    createActiveConsent($user);

    expect(app(ConsentService::class)->hasActiveConsent($user))->toBeTrue();
});

test('admin can revoke a consent', function () {
    $admin = adminForConsent();
    $user = plainUserForConsent();
    $consent = createActiveConsent($user);

    $response = $this->actingAs($admin)->patchJson(
        route('security.users.consents.revoke', [$user, $consent])
    );

    $response->assertSuccessful();
    expect($consent->fresh()->revoked_at)->not->toBeNull();
});

test('no active consent after revoke', function () {
    $admin = adminForConsent();
    $user = plainUserForConsent();
    $consent = createActiveConsent($user);

    $this->actingAs($admin)->patchJson(
        route('security.users.consents.revoke', [$user, $consent])
    );

    expect(app(ConsentService::class)->hasActiveConsent($user))->toBeFalse();
});

test('multiple consents coexist — newest active, old revoked', function () {
    $admin = adminForConsent();
    $user = plainUserForConsent();

    $first = createActiveConsent($user);
    $second = createActiveConsent($user);

    $this->actingAs($admin)->patchJson(
        route('security.users.consents.revoke', [$user, $first])
    );

    expect(UserConsent::where('user_id', $user->id)->count())->toBe(2);
    expect($first->fresh()->revoked_at)->not->toBeNull();
    expect($second->fresh()->revoked_at)->toBeNull();
    expect(app(ConsentService::class)->hasActiveConsent($user))->toBeTrue();
});
