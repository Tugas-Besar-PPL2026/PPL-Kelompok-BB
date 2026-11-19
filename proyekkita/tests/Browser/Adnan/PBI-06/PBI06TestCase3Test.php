<?php

namespace Tests\Browser\Adnan\PBI06;

use App\Models\Category;
use Laravel\Dusk\Browser;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

class PBI06TestCase3Test extends DuskTestCase
{
    use UsesDuskTestDatabase;

    public function test_tc_pbi6_003_tambah_kategori_nama_kosong(): void
    {
        $jumlahAwal = Category::count();

        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->type('email', 'admin@proyekkita.test')
                ->type('password', 'password')
                ->press('Masuk')
                ->waitForLocation('/admin/data-master')
                ->script("document.getElementById('name').removeAttribute('required');");

            $browser->type('input[id="name"]', '   ')
                ->press('+ Tambah')
                ->waitForText('Nama kategori wajib diisi')
                ->assertSee('Nama kategori wajib diisi');
        });

        $this->assertEquals($jumlahAwal, Category::count());
    }
}
