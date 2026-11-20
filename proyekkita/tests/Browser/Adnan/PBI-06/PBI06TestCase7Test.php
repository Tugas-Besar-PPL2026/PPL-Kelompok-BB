<?php

namespace Tests\Browser\Adnan\PBI06;

use App\Models\Category;
use Laravel\Dusk\Browser;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

class PBI06TestCase7Test extends DuskTestCase
{
    use UsesDuskTestDatabase;


    public function test_tc_pbi6_007_ubah_nama_kategori(): void
    {
        $category = Category::whereNotIn('name', [
            'Teknologi & Web',
            'Riset Pasar & Konten',
            'Pemasaran & Media Sosial',
        ])->firstOrFail();

        $namaBaru = 'Desain & Branding ' . date('His');

        $this->browse(function (Browser $browser) use ($category, $namaBaru) {
            $browser->visit('/login')
                ->type('email', 'admin@proyekkita.test')
                ->type('password', 'password')
                ->press('Masuk')
                ->waitForLocation('/admin/data-master')
                ->click("#cat-row-{$category->id} button[title='Ubah Kategori']")
                ->waitFor("#cat-edit-{$category->id}")
                ->clear("#cat-edit-{$category->id} input[name='name']")
                ->type("#cat-edit-{$category->id} input[name='name']", $namaBaru)
                ->click("#cat-edit-{$category->id} button[type='submit']")
                ->assertSee($namaBaru);
        });

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => $namaBaru,
        ]);
    }
}
