<?php

namespace Tests\Browser\Adnan\PBI06;

use App\Models\Category;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PBI06TestCase9Test extends DuskTestCase
{
    public function test_tc_pbi6_009_simpan_edit_tanpa_mengubah_nama(): void
    {
        $category = Category::where('name', 'Teknologi & Web')->firstOrFail();

        $this->browse(function (Browser $browser) use ($category) {
            $browser->visit('/login')
                ->type('email', 'admin@proyekkita.test')
                ->type('password', 'password')
                ->press('Masuk')
                ->waitForLocation('/admin/data-master')
                ->click("#cat-row-{$category->id} button[title='Ubah Kategori']")
                ->waitFor("#cat-edit-{$category->id}")
                ->click("#cat-edit-{$category->id} button[type='submit']")
                ->pause(500)
                ->assertDontSee('sudah terdaftar')
                ->assertSee('Teknologi & Web');
        });

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Teknologi & Web',
        ]);
    }
}