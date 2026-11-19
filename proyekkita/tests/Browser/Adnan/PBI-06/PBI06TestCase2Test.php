<?php

namespace Tests\Browser\Adnan\PBI06;

use Laravel\Dusk\Browser;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

class PBI06TestCase2Test extends DuskTestCase
{
    use UsesDuskTestDatabase;

    public function test_tc_pbi6_002_tambah_kategori_valid(): void
    {
        $namaKategori = 'Penerjemahan & Bahasa ' . date('His');

        $this->browse(function (Browser $browser) use ($namaKategori) {
            $browser->visit('/login')
                ->type('email', 'admin@proyekkita.test')
                ->type('password', 'password')
                ->press('Masuk')
                ->waitForLocation('/admin/data-master')
                ->type('input[id="name"]', $namaKategori)
                ->press('+ Tambah')
                ->waitForText($namaKategori)
                ->assertSee($namaKategori);
        });

        $this->assertDatabaseHas('categories', [
            'name' => $namaKategori,
            'is_active' => 1,
        ]);
    }
}
