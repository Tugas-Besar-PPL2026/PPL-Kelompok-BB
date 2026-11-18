<?php

namespace Tests\Browser\Adnan\PBI06;

use App\Models\Category;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PBI06TestCase11Test extends DuskTestCase
{

    public function test_tc_pbi6_011_mengaktifkan_kembali_kategori(): void
    {
        $category = Category::where('is_active', false)->firstOrFail();

        $this->browse(function (Browser $browser) use ($category) {
            $browser->visit('/login')
                ->type('email', 'admin@proyekkita.test')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->type('password', 'password')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->press('Masuk')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->waitForLocation('/admin/data-master')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->assertSeeIn("#cat-row-{$category->id}", 'Nonaktif')
                ->click("#cat-row-{$category->id} form button[type='submit']")
                ->pause((int) env('DUSK_JEDA', 1000))
                ->pause((int) env('DUSK_JEDA', 1000))
                ->assertSeeIn("#cat-row-{$category->id}", 'Aktif');
        });

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'is_active' => 1,
        ]);
    }
}
