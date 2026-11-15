<?php

namespace Tests\Browser;

use App\Enums\UserRole;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class KelolaKeterampilanTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_admin_dapat_menambah_keterampilan_baru_di_browser(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit(route('admin.master.index'))
                ->assertSee('Keterampilan')
                ->type('@input-keterampilan-baru', 'Figma')
                ->click('@tambah-keterampilan')
                ->pause(500)
                ->assertSee('Figma');
        });

        $this->assertDatabaseHas('skills', [
            'name' => 'Figma',
            'is_active' => true,
        ]);
    }

    public function test_admin_melihat_peringatan_duplikat_saat_menambah_keterampilan_yang_sudah_ada(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Skill::create([
            'name' => 'Laravel',
            'is_active' => true,
        ]);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit(route('admin.master.index'))
                ->type('@input-keterampilan-baru', 'laravel')
                ->waitFor('@galat-tambah-keterampilan')
                ->assertSeeIn('@galat-tambah-keterampilan', 'Keterampilan "Laravel" sudah terdaftar. Silahkan tambah keterampilan yang lain.')
                ->assertDisabled('@tambah-keterampilan');
        });
    }

    public function test_admin_dapat_mengubah_nama_keterampilan_di_browser(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $skill = Skill::create([
            'name' => 'Desain UI',
            'is_active' => true,
        ]);

        $this->browse(function (Browser $browser) use ($admin, $skill) {
            $browser->loginAs($admin)
                ->visit(route('admin.master.index'))
                ->assertSee('Desain UI')
                ->click("@ubah-keterampilan-{$skill->id}")
                ->waitFor("@form-ubah-keterampilan-{$skill->id}")
                ->clear("@input-ubah-keterampilan-{$skill->id}")
                ->type("@input-ubah-keterampilan-{$skill->id}", 'UI/UX Design')
                ->click("@simpan-ubah-keterampilan-{$skill->id}")
                ->pause(500)
                ->assertSee('UI/UX Design');
        });

        $this->assertDatabaseHas('skills', [
            'id' => $skill->id,
            'name' => 'UI/UX Design',
        ]);
    }

    public function test_admin_dapat_menonaktifkan_keterampilan_di_browser(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $skill = Skill::create([
            'name' => 'Copywriting',
            'is_active' => true,
        ]);

        $this->browse(function (Browser $browser) use ($admin, $skill) {
            $browser->loginAs($admin)
                ->visit(route('admin.master.index'))
                ->assertSee('Copywriting')
                ->assertSeeIn("@status-keterampilan-{$skill->id}", 'Aktif')
                ->click("@toggle-keterampilan-{$skill->id}")
                ->pause(500)
                ->assertSeeIn("@status-keterampilan-{$skill->id}", 'Nonaktif');
        });

        $this->assertDatabaseHas('skills', [
            'id' => $skill->id,
            'is_active' => false,
        ]);
    }
}