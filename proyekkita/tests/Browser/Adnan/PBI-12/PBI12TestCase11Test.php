<?php

namespace Tests\Browser\Adnan\PBI12;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PBI12TestCase11Test extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_tc_pbi12_011_antrean_moderasi_kosong(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->browse(function (Browser $browser) use ($admin) {
            $jeda = (int) env('DUSK_JEDA', 1000);

            $browser->loginAs($admin)
                ->visit(route('admin.moderasi.index'))
                ->pause($jeda)
                ->assertSee('Belum ada proyek pada status ini.')
                ->assertSee('Pilih proyek dari daftar antrean di sebelah kiri untuk melihat detail dan melakukan moderasi.');
        });
    }
}