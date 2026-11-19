<?php

namespace Tests\Browser\Adnan\PBI06;

use App\Models\Category;
use Laravel\Dusk\Browser;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

class PBI06TestCase10Test extends DuskTestCase
{
    use UsesDuskTestDatabase;

    public function test_tc_pbi6_010_menonaktifkan_kategori(): void
    {
        $category = Category::create([
            'name' => 'Kategori Aktif ' . date('His'),
            'is_active' => true,
        ]);

        $this->browse(function (Browser $browser) use ($category) {
            $browser->visit('/login')
                ->type('email', 'admin@proyekkita.test')
                ->type('password', 'password')
                ->press('Masuk')
                ->waitForLocation('/admin/data-master')
                ->assertSeeIn("#cat-row-{$category->id}", 'Aktif')
                ->click("#cat-row-{$category->id} form button[type='submit']")
                ->waitForTextIn("#cat-row-{$category->id}", 'Nonaktif')
                ->assertSeeIn("#cat-row-{$category->id}", 'Nonaktif');
        });

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'is_active' => 0,
        ]);
    }
}
