<?php

use App\Mail\InvitationMail;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Permission::firstOrCreate(['name' => 'users.invite', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Profesor', 'guard_name' => 'web']);
});

function userWithInvitePerm(): User
{
    $user = User::factory()->create();
    $user->givePermissionTo('users.invite');

    return $user;
}

test('unauthenticated cannot send invitation', function () {
    $this->post('/security/invitations', [])->assertRedirect('/login');
});

test('user without users.invite gets 403', function () {
    $this->actingAs(User::factory()->create())
        ->post('/security/invitations', ['email' => 'x@test.com', 'role' => 'Profesor'])
        ->assertForbidden();
});

test('store creates invitation and sends mail', function () {
    Mail::fake();

    $actor = userWithInvitePerm();

    $this->actingAs($actor)
        ->post('/security/invitations', ['email' => 'invite@test.com', 'role' => 'Profesor'])
        ->assertRedirect(route('security.users.index'));

    expect(Invitation::where('email', 'invite@test.com')->pending()->exists())->toBeTrue();

    $inv = Invitation::where('email', 'invite@test.com')->pending()->first();
    expect($inv->invited_by)->toBe($actor->id);
    expect($inv->expires_at->greaterThan(now()->addHours(47)))->toBeTrue();

    Mail::assertSent(InvitationMail::class, fn ($mail) => $mail->hasTo('invite@test.com'));
});

test('store cancels existing pending invitation for same email before creating new one', function () {
    Mail::fake();

    $actor = userWithInvitePerm();
    $old = Invitation::factory()->create(['email' => 'dup@test.com']);

    $this->actingAs($actor)
        ->post('/security/invitations', ['email' => 'dup@test.com', 'role' => 'Profesor'])
        ->assertRedirect(route('security.users.index'));

    expect(Invitation::find($old->id))->toBeNull()
        ->and(Invitation::where('email', 'dup@test.com')->pending()->count())->toBe(1);
});

test('destroy deletes a pending invitation', function () {
    $actor = userWithInvitePerm();
    $invitation = Invitation::factory()->create();

    $this->actingAs($actor)
        ->delete(route('security.invitations.destroy', $invitation))
        ->assertRedirect(route('security.users.index'));

    expect(Invitation::find($invitation->id))->toBeNull();
});

test('destroy cannot delete a used invitation', function () {
    $actor = userWithInvitePerm();
    $invitation = Invitation::factory()->used()->create();

    $this->actingAs($actor)
        ->delete(route('security.invitations.destroy', $invitation))
        ->assertForbidden();
});
