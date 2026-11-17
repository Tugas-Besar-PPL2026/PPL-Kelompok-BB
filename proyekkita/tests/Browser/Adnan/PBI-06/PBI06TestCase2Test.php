<?php

namespace Tests\Browser\Adnan\PBI06;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PBI06TestCase2Test extends DuskTestCase
{

    public function test_tc_pbi6_002_tambah_kategori_valid(): void
    {
        $namaKategori = 'Penerjemahan & Bahasa ' . date('His');

        $this->browse(function (Browser$browser) use ($namaKategori) {$browser->visit('/login')
                ->type('email', 'admin@proyekkita.test')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->type('password', 'password')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->press('Masuk')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->waitForLocation('/admin/data-master')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->type('input[id="name"]', $namaKategori)
                ->pause((int) env('DUSK_JEDA', 1000))
                ->press('+ Tambah')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->pause((int) env('DUSK_JEDA', 1000))
                ->assertSee($namaKategori);
        });

        $this->assertDatabaseHas('categories', [
            'name' => $namaKategori,
            'is_active' => 1,
        ]);
    }
}


