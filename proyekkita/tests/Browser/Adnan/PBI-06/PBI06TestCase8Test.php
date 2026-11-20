<?php

namespace Tests\Browser\Adnan\PBI06;

use App\Models\Category;
use Laravel\Dusk\Browser;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

class PBI06TestCase8Test extends DuskTestCase
{
    use UsesDuskTestDatabase;


    public function test_tc_pbi6_008_ubah_nama_jadi_nama_kategori_lain(): void
    {
        $category = Category::where('name', 'Riset Pasar & Konten')->firstOrFail();

        $this->browse(function (Browser $browser) use ($category) {
            $browser->visit('/login')
                ->type('email', 'admin@proyekkita.test')
                ->type('password', 'password')
                ->press('Masuk')
                ->waitForLocation('/admin/data-master')
                ->click("#cat-row-{$category->id} button[title='Ubah Kategori']")
                ->waitFor("#cat-edit-{$category->id}")
                ->clear("#cat-edit-{$category->id} input[name='name']")
                ->type("#cat-edit-{$category->id} input[name='name']", 'Teknologi & Web')
                ->click("#cat-edit-{$category->id} button[type='submit']")
                ->assertSee('sudah terdaftar');
        });

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Riset Pasar & Konten',
        ]);
    }
}
