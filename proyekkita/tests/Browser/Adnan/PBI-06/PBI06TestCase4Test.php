<?php

namespace Tests\Browser\Adnan\PBI06;

use App\Models\Category;
use Laravel\Dusk\Browser;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

class PBI06TestCase4Test extends DuskTestCase
{
    use UsesDuskTestDatabase;


    public function test_tc_pbi6_004_tambah_kategori_nama_duplikat(): void
    {
        $jumlahAwal = Category::count();

        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->type('email', 'admin@proyekkita.test')
                ->type('password', 'password')
                ->press('Masuk')
                ->waitForLocation('/admin/data-master')
                ->type('input[id="name"]', 'Teknologi & Web')
                ->press('+ Tambah')
                ->assertSee('sudah terdaftar');
        });

        $this->assertEquals($jumlahAwal, Category::count());
    }
}
