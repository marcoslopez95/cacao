<?php

use Laravel\Dusk\Browser;

test('app is accessible', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/')
            ->assertSourceHas('<html');
    });
});
