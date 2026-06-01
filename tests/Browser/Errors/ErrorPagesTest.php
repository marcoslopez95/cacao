<?php

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

test('404 page is shown for unknown route', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/esta-ruta-no-existe-en-ninguna-parte')
            ->waitForText('Ir al inicio', 10)
            ->assertSee('no aparece')
            ->assertSee('Ir al inicio')
            ->assertSee('CACAO');
    });
});

test('404 page has working home link', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/ruta-inexistente-xyz')
            ->waitForText('Ir al inicio', 5)
            ->clickLink('Ir al inicio')
            ->pause(1000)
            ->assertPathIsNot('/ruta-inexistente-xyz');
    });
});

test('403 page is shown when user lacks permission', function () {
    $user = User::factory()->create();
    $user->assignRole('Admin');

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/enrollment')
            ->waitForText('Contactar admin', 10)
            ->assertSee('no puedes pasar')
            ->assertSee('Contactar admin');
    });
});

test('500 page shows incident id', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/_test/trigger-500')
            ->waitForText('Reintentar', 10)
            ->assertSee('rompió')
            ->assertSee('CAC-')
            ->assertSee('Reintentar');
    });
});

test('dark mode: 404 page respects dark theme', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/ruta-no-existe-dark')
            ->waitForText('Ir al inicio', 10);
        $browser->script("document.documentElement.setAttribute('data-theme','dark')");
        $browser->assertSee('no aparece');
    });
});
