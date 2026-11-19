<?php

namespace Tests\Browser\Adnan\PBI06;

use Laravel\Dusk\Browser;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

class PBI06TestCase12Test extends DuskTestCase
{
    use UsesDuskTestDatabase;

    public function test_tc_pbi6_012_tidak_ada_fitur_hapus_kategori(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->type('email', 'admin@proyekkita.test')
                ->type('password', 'password')
                ->press('Masuk')
                ->waitForLocation('/admin/data-master')
                ->assertSee('Data master tidak dihapus')
                ->assertMissing("input[name='_method'][value='DELETE']")
                ->assertDontSee('Hapus Kategori');
        });
    }
}
