<?php

namespace Tests\Browser\Adnan\PBI06;

use App\Models\Category;
use Laravel\Dusk\Browser;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

class PBI06TestCase5Test extends DuskTestCase
{
    use UsesDuskTestDatabase;

    public function test_tc_pbi6_005_duplikat_beda_huruf_besar_kecil(): void
    {
        Category::firstOrCreate(
            ['name' => 'Teknologi & Web'],
            ['is_active' => true]
        );

        $jumlahAwal = Category::count();

        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->type('email', 'admin@proyekkita.test')
                ->type('password', 'password')
                ->press('Masuk')
                ->waitForLocation('/admin/data-master')
                ->type('input[id="name"]', 'teknologi & web')
                ->press('+ Tambah')
                ->waitForText('sudah terdaftar')
                ->assertSee('sudah terdaftar');
        });

        $this->assertEquals($jumlahAwal, Category::count());
    }
}
