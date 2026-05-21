<?php

use App\Models\Career;
use App\Models\Pensum;
use App\Models\User;
use Laravel\Dusk\Browser;

// Note: Browser/Dusk tests run against the real database without transaction rollback.
// Roles must exist — seed with: php artisan db:seed --class=PermissionSeeder && php artisan db:seed --class=RoleSeeder

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
