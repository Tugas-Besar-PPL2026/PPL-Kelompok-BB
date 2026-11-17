<?php

namespace Tests\Browser\Adnan\PBI06;

use App\Models\Category;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PBI06TestCase3Test extends DuskTestCase
{

    public function test_tc_pbi6_003_tambah_kategori_nama_kosong(): void
    {
        $jumlahAwal = Category::count();

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
                ->script("document.getElementById('name').removeAttribute('required');");

            $browser->type('input[id="name"]', '   ')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->press('+ Tambah')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->pause((int) env('DUSK_JEDA', 1000))
                ->assertSee('Nama kategori wajib diisi');
        });

        $this->assertEquals($jumlahAwal, Category::count());
    }
}
