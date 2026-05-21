<?php

use App\Models\User;
use Laravel\Dusk\Browser;

// Note: Browser/Dusk tests run against the real database without transaction rollback.
// Roles must exist — seed with: php artisan db:seed --class=PermissionSeeder && php artisan db:seed --class=RoleSeeder
// Sections are managed under scheduling/sections (not academic/), with separate university and school routes.

test('admin can view sections list', function () {
    $user = User::factory()->create();
    $user->assignRole('Admin');

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/scheduling/sections/university')
            ->waitForText('Secciones', 10)
            ->assertSee('Secciones')
            ->assertPathIs('/scheduling/sections/university');
    });
});
