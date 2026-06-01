<?php

use App\Actions\Security\CreateUserAction;
use App\Http\Wrappers\Security\UserWrapper;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\AdminTeamSeeder;
use Database\Seeders\CatalogsSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([CatalogsSeeder::class, PermissionSeeder::class, RoleSeeder::class, UserSeeder::class]);
});

test('admin team is created with correct attributes', function () {
    $this->seed(AdminTeamSeeder::class);

    $team = Team::where('slug', 'admin')->first();

    expect($team)->not->toBeNull()
        ->and($team->name)->toBe('Administración')
        ->and($team->is_personal)->toBeFalse();
});

test('seeder is idempotent — does not create duplicate team', function () {
    $this->seed(AdminTeamSeeder::class);
    $firstId = Team::where('slug', 'admin')->value('id');

    $this->seed(AdminTeamSeeder::class);

    expect(Team::where('slug', 'admin')->count())->toBe(1)
        ->and(Team::where('slug', 'admin')->value('id'))->toBe($firstId);
});

test('seeder assigns all admin users as team members', function () {
    $this->seed(AdminTeamSeeder::class);

    $adminTeam = Team::where('slug', 'admin')->first();
    $admins = User::role('Admin')->get();

    expect($admins)->not->toBeEmpty();

    foreach ($admins as $admin) {
        expect($admin->belongsToTeam($adminTeam))->toBeTrue();
    }
});

test('seeder does not create duplicate memberships on second run', function () {
    $this->seed(AdminTeamSeeder::class);
    $this->seed(AdminTeamSeeder::class);

    $adminTeam = Team::where('slug', 'admin')->first();
    $admins = User::role('Admin')->get();

    foreach ($admins as $admin) {
        $membershipCount = $adminTeam->members()
            ->where('user_id', $admin->id)
            ->count();

        expect($membershipCount)->toBe(1);
    }
});

test('new admin user is added to admin team by CreateUserAction', function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->seed(AdminTeamSeeder::class);

    $wrapper = new UserWrapper(new Collection([
        'first_name' => 'Nuevo',
        'last_name' => 'Admin',
        'email' => 'nuevo-admin@cacao.edu.ve',
        'password_mode' => 'manual',
        'password' => 'password',
        'role' => 'Admin',
    ]));

    $newAdmin = app(CreateUserAction::class)->handle($wrapper);
    $adminTeam = Team::where('slug', 'admin')->first();

    expect($newAdmin->belongsToTeam($adminTeam))->toBeTrue();
});
