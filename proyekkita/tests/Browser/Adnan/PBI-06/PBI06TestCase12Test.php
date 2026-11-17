<?php

namespace Tests\Browser\Adnan\PBI06;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PBI06TestCase12Test extends DuskTestCase
{

    public function test_tc_pbi6_012_tidak_ada_fitur_hapus_kategori(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->type('email', 'admin@proyekkita.test')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->type('password', 'password')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->press('Masuk')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->waitForLocation('/admin/data-master')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->assertSee('Data master tidak dihapus')
                ->assertMissing("input[name='_method'][value='DELETE']")
                ->assertDontSee('Hapus Kategori');
        });
    }
}
