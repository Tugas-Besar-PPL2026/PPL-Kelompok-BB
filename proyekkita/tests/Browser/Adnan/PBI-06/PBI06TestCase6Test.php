<?php

namespace Tests\Browser\Adnan\PBI06;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PBI06TestCase6Test extends DuskTestCase
{
    public function test_tc_pbi6_006_batas_panjang_nama_maksimal(): void
    {
        $teks100 = str_pad('Kat100_' . date('His') . '_', 100, 'x');
        $teks101 = str_pad('Kat101_' . date('His') . '_', 101, 'y');

        $this->browse(function (Browser $browser) use ($teks100, $teks101) {
            $browser->visit('/login')
                ->type('email', 'admin@proyekkita.test')
                ->type('password', 'password')
                ->press('Masuk')
                ->waitForLocation('/admin/data-master')
                ->type('input[id="name"]', $teks100)
                ->press('+ Tambah')
                ->pause(500)
                ->assertSee($teks100)
                ->type('input[id="name"]', $teks101)
                ->press('+ Tambah')
                ->pause(500)
                ->assertSee('100');
        });

        $this->assertDatabaseHas('categories', ['name' => $teks100]);
        $this->assertDatabaseMissing('categories', ['name' => $teks101]);
    }
}