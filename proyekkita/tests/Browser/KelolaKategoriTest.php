<?php

namespace Tests\Browser;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class KelolaKategoriTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_admin_dapat_menambah_kategori_baru_di_browser(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit(route('admin.master.index'))
                ->assertSee('Kategori Proyek')
                ->type('input[id="name"]', 'Penerjemahan & Bahasa')
                ->press('+ Tambah')
                ->pause(500)
                ->assertSee('Penerjemahan & Bahasa');
        });

        $this->assertDatabaseHas('categories', [
            'name' => 'Penerjemahan & Bahasa',
            'is_active' => true,
        ]);
    }

    public function test_admin_dapat_mengubah_nama_kategori_di_browser(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::create([
            'name' => 'Desain Grafis',
            'is_active' => true,
        ]);

        $this->browse(function (Browser $browser) use ($admin, $category) {
            $browser->loginAs($admin)
                ->visit(route('admin.master.index'))
                ->assertSee('Desain Grafis')
                ->click("#cat-row-{$category->id} button[title='Ubah Kategori']")
                ->waitFor("#cat-edit-{$category->id}")
                ->clear("#cat-edit-{$category->id} input[name='name']")
                ->type("#cat-edit-{$category->id} input[name='name']", 'Desain & Kreatif Digital')
                ->click("#cat-edit-{$category->id} button[type='submit']")
                ->pause(500)
                ->assertSee('Desain & Kreatif Digital');
        });

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Desain & Kreatif Digital',
        ]);
    }

    public function test_admin_dapat_menonaktifkan_kategori_di_browser(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::create([
            'name' => 'Pengembangan Web',
            'is_active' => true,
        ]);

        $this->browse(function (Browser $browser) use ($admin, $category) {
            $browser->loginAs($admin)
                ->visit(route('admin.master.index'))
                ->assertSee('Pengembangan Web')
                ->assertSeeIn("#cat-row-{$category->id}", 'Aktif')
                ->click("#cat-row-{$category->id} form button[type='submit']")
                ->pause(500)
                ->assertSeeIn("#cat-row-{$category->id}", 'Nonaktif');
        });

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'is_active' => false,
        ]);
    }
}