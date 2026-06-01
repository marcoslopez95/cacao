<?php

use App\Models\Career;
use App\Models\Pensum;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Spatie\Permission\PermissionRegistrar;

uses(DatabaseMigrations::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

test('admin can view subjects list', function () {
    $user = User::factory()->create();
    $user->assignRole('Admin');

    $career = Career::factory()->create(['name' => 'Carrera Materias Dusk']);
    $pensum = Pensum::factory()->create(['career_id' => $career->id, 'name' => 'Plan Materias 2024']);

    $this->browse(function (Browser $browser) use ($user, $career, $pensum) {
        $browser->loginAs($user)
            ->visit("/academic/careers/{$career->id}/pensums/{$pensum->id}/subjects")
            ->waitForText('Materias', 10)
            ->assertSee('Materias')
            ->assertPathIs("/academic/careers/{$career->id}/pensums/{$pensum->id}/subjects");
    });
});
