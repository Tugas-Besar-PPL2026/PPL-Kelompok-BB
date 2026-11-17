<?php

namespace Tests\Browser\Adnan\PBI12;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PBI12TestCase10Test extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_tc_pbi12_010_pembatasan_hak_akses_guest_dan_non_admin(): void
    {
        $umkm = User::factory()->create(['role' => UserRole::Umkm]);
        $kandidat = User::factory()->create(['role' => UserRole::Kandidat]);

        $this->browse(function (Browser $browser) use ($umkm, $kandidat) {
            $jeda = (int) env('DUSK_JEDA', 1000);

            // 1. Guest diarahkan ke login
            $browser->logout()
                ->visit('/admin/moderasi-proyek')
                ->pause($jeda)
                ->assertPathIs('/login');

            // 2. Non-admin (UMKM) ditolak 403
            $browser->loginAs($umkm)
                ->visit('/admin/moderasi-proyek')
                ->pause($jeda)
                ->assertSee('403');

            // 3. Non-admin (Kandidat) ditolak 403
            $browser->loginAs($kandidat)
                ->visit('/admin/moderasi-proyek')
                ->pause($jeda)
                ->assertSee('403');
        });
    }
}
