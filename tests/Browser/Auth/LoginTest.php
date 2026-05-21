<?php

use App\Models\User;
use Laravel\Dusk\Browser;

test('user can login with correct credentials', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $this->browse(function (Browser $browser) use ($user, $team) {
        $browser->visit('/login')
            ->waitFor('#email')
            ->type('#email', $user->email)
            ->type('#password', 'password')
            ->press('Iniciar sesión')
            ->waitForLocation("/{$team->slug}/dashboard")
            ->assertPathIs("/{$team->slug}/dashboard");
    });
});

test('login fails with wrong password', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->logout()
            ->visit('/login')
            ->waitFor('#email')
            ->type('#email', $user->email)
            ->type('#password', 'wrong-password')
            ->press('Iniciar sesión')
            ->pause(1000)
            ->assertPathIs('/login')
            ->assertSee('credentials');
    });
});

test('login fails with nonexistent email', function () {
    $this->browse(function (Browser $browser) {
        $browser->logout()
            ->visit('/login')
            ->waitFor('#email')
            ->type('#email', 'noexiste@example.com')
            ->type('#password', 'any-password')
            ->press('Iniciar sesión')
            ->pause(1000)
            ->assertPathIs('/login')
            ->assertSee('credentials');
    });
});

test('guest is redirected to login when visiting protected route', function () {
    $this->browse(function (Browser $browser) {
        $browser->logout()
            ->visit('/security/users')
            ->waitForLocation('/login')
            ->assertPathIs('/login');
    });
});
