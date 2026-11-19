<?php

namespace Tests\Browser\Adnan\PBI06;

use Laravel\Dusk\Browser;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

class PBI06TestCase14Test extends DuskTestCase
{
    use UsesDuskTestDatabase;

    public function test_tc_pbi6_014_akses_tanpa_login(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->driver->manage()->deleteAllCookies();

            $browser->visit('/admin/data-master')
                ->waitForLocation('/login')
                ->assertPathIs('/login');
        });
    }
}
