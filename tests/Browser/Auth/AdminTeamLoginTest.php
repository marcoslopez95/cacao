<?php

use App\Actions\Security\CreateUserAction;
use App\Enums\TeamRole;
use App\Http\Wrappers\Security\UserWrapper;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\AdminTeamSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Collection;
use Laravel\Dusk\Browser;
use Spatie\Permission\PermissionRegistrar;

uses(DatabaseMigrations::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
    $this->seed(AdminTeamSeeder::class);
});

function adminWithTeam(): User
{
    $user = User::factory()->create();
    $user->assignRole('Admin');

    $adminTeam = Team::where('slug', 'admin')->first();
    $adminTeam->members()->attach($user, ['role' => TeamRole::Admin->value]);

    return $user;
}

// UC-QA-01 — Login Admin redirige a /admin/dashboard
test('UC-QA-01 admin login redirects to /admin/dashboard', function () {
    $admin = adminWithTeam();

    $this->browse(function (Browser $browser) use ($admin) {
        $browser->visit('/login')
            ->waitFor('#email')
            ->type('#email', $admin->email)
            ->type('#password', 'password')
            ->press('Iniciar sesión')
            ->waitForLocation('/admin/dashboard', 10)
            ->assertPathIs('/admin/dashboard');
    });

    expect(User::find($admin->id)->current_team_id)
        ->toBe(Team::where('slug', 'admin')->value('id'));
});

// UC-QA-02 — Admin creado via CreateUserAction tiene membresía y accede a /admin/dashboard
test('UC-QA-02 new admin created via action belongs to team and redirects on login', function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $wrapper = new UserWrapper(new Collection([
        'first_name' => 'Nuevo',
        'last_name' => 'Admin',
        'email' => 'nuevo-admin-dusk@cacao.edu.ve',
        'password_mode' => 'manual',
        'password' => 'password123',
        'role' => 'Admin',
    ]));

    $newAdmin = app(CreateUserAction::class)->handle($wrapper);
    $adminTeam = Team::where('slug', 'admin')->first();

    expect($newAdmin->belongsToTeam($adminTeam))->toBeTrue();

    $this->browse(function (Browser $browser) use ($newAdmin) {
        $browser->visit('/login')
            ->waitFor('#email')
            ->type('#email', $newAdmin->email)
            ->type('#password', 'password123')
            ->press('Iniciar sesión')
            ->waitForLocation('/admin/dashboard', 10)
            ->assertPathIs('/admin/dashboard');
    });
});

// UC-QA-03 — Otros roles no redirigen a /admin/dashboard
test('UC-QA-03 non-admin roles do not redirect to /admin/dashboard', function (string $role) {
    $user = User::factory()->create();
    $user->assignRole($role);

    $this->browse(function (Browser $browser) use ($user) {
        $browser->visit('/login')
            ->waitFor('#email')
            ->type('#email', $user->email)
            ->type('#password', 'password')
            ->press('Iniciar sesión')
            ->pause(2500);

        expect($browser->driver->getCurrentURL())->not->toContain('/admin/dashboard');
    });
})->with([
    'Profesor',
    'Estudiante',
    'Representante',
]);
