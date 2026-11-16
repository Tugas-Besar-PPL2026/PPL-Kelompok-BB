<?php

namespace Tests\Browser\Adnan\PBI07;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PBI07TestCase16Test extends DuskTestCase
{
    public function test_tc_pbi7_016_akses_tanpa_login(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->driver->manage()->deleteAllCookies();

            $browser->visit('/admin/data-master')
                ->waitForLocation('/login')
                ->assertPathIs('/login');
        });
    }
}