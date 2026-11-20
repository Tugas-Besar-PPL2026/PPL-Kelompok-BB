<?php

namespace Tests\Browser\Adnan\PBI06;

use App\Models\Category;
use Laravel\Dusk\Browser;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

class PBI06TestCase11Test extends DuskTestCase
{
    use UsesDuskTestDatabase;


    public function test_tc_pbi6_011_mengaktifkan_kembali_kategori(): void
    {
        $category = Category::where('is_active', false)->firstOrFail();

        $this->browse(function (Browser $browser) use ($category) {
            $browser->visit('/login')
                ->type('email', 'admin@proyekkita.test')
                ->type('password', 'password')
                ->press('Masuk')
                ->waitForLocation('/admin/data-master')
                ->assertSeeIn("#cat-row-{$category->id}", 'Nonaktif')
                ->click("#cat-row-{$category->id} form button[type='submit']")
                ->assertSeeIn("#cat-row-{$category->id}", 'Aktif');
        });

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'is_active' => 1,
        ]);
    }
}
