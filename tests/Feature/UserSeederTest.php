<?php

use App\Models\Professor;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\CatalogsSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(CatalogsSeeder::class);
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

it('creates professor sub-record for Profesor role', function () {
    $this->seed(UserSeeder::class);

    $user = User::whereHas('roles', fn ($q) => $q->where('name', 'Profesor'))->first();

    expect($user)->not->toBeNull();
    expect(Professor::where('user_id', $user->id)->exists())->toBeTrue();
});

it('creates student sub-record for Estudiante role', function () {
    $this->seed(UserSeeder::class);

    $user = User::whereHas('roles', fn ($q) => $q->where('name', 'Estudiante'))->first();

    expect($user)->not->toBeNull();
    expect(Student::where('user_id', $user->id)->exists())->toBeTrue();
});

it('seeder is idempotent — running twice does not duplicate sub-records', function () {
    $this->seed(UserSeeder::class);
    $this->seed(UserSeeder::class);

    $user = User::whereHas('roles', fn ($q) => $q->where('name', 'Profesor'))->first();

    expect(Professor::where('user_id', $user->id)->count())->toBe(1);
});
