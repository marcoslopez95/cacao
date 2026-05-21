<?php

use App\Models\Career;
use App\Models\Pensum;
use App\Models\User;
use Laravel\Dusk\Browser;

// Note: Browser/Dusk tests run against the real database without transaction rollback.
// Roles must exist — seed with: php artisan db:seed --class=PermissionSeeder && php artisan db:seed --class=RoleSeeder
// Test data is not cleaned up (standard Dusk pattern to avoid FK cascade issues).

test('admin can view careers list', function () {
    $user = User::factory()->create();
    $user->assignRole('Admin');

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/academic/careers')
            ->waitForText('Carreras', 10)
            ->assertSee('Carreras')
            ->assertPathIs('/academic/careers');
    });
});

test('admin can view career pensums', function () {
    $user = User::factory()->create();
    $user->assignRole('Admin');

    $career = Career::factory()->create(['name' => 'Ingeniería de Prueba Dusk']);
    Pensum::factory()->create(['career_id' => $career->id, 'name' => 'Plan Dusk 2024']);

    $this->browse(function (Browser $browser) use ($user, $career) {
        $browser->loginAs($user)
            ->visit("/academic/careers/{$career->id}/pensums")
            ->waitForText('Pensums', 10)
            ->assertSee('Pensums')
            ->assertSee('Ingeniería de Prueba Dusk')
            ->assertPathIs("/academic/careers/{$career->id}/pensums");
    });
});
