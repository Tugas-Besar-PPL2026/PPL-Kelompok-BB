<?php

namespace Tests\Browser\Adnan\PBI06;

use App\Models\Category;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PBI06TestCase1Test extends DuskTestCase
{

    public function test_tc_pbi6_001_menampilkan_halaman_data_master(): void
    {
        $kategoriPertama = Category::firstOrFail();

        $this->browse(function (Browser $browser) use ($kategoriPertama) {
            $browser->visit('/login')
                ->type('email', 'admin@proyekkita.test')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->type('password', 'password')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->press('Masuk')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->waitForLocation('/admin/data-master')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->assertSee('Data Master')
                ->assertSee('Kategori Proyek')
                ->assertSee($kategoriPertama->name)
                ->assertSee('kategori');
        });
    }
}
