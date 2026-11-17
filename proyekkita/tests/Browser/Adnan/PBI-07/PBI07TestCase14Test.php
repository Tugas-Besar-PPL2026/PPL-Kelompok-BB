<?php

namespace Tests\Browser\Adnan\PBI07;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PBI07TestCase14Test extends DuskTestCase
{
    public function test_tc_pbi7_014_tidak_ada_fitur_hapus_keterampilan(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->type('email', 'admin@proyekkita.test')
                ->type('password', 'password')
                ->press('Masuk')
                ->waitForLocation('/admin/data-master')
                ->assertSee('Data master tidak dihapus')
                ->assertMissing("@kartu-keterampilan input[name='_method'][value='DELETE']")
                ->assertDontSeeIn('@kartu-keterampilan', 'Hapus');
        });
    }
}