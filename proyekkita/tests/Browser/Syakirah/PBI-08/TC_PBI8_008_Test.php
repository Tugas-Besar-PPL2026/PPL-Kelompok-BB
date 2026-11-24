<?php

namespace Tests\Browser\PBI08;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TC_PBI8_023_Test extends DuskTestCase
{
    public function test_kandidat_dan_umkm_membuka_antrean_verifikasi_ditolak_403(): void
    {
        $this->browse(function (Browser $browser) {
            $kandidat = User::where('email', 'kandidat@proyekkita.test')->firstOrFail();
            $umkm = User::where('email', 'umkm@proyekkita.test')->firstOrFail();

            $browser->loginAs($kandidat)
                ->visit('/admin/verifikasi')
                ->assertSee('403')
                ->loginAs($umkm)
                ->visit('/admin/verifikasi')
                ->assertSee('403');
        });
    }
}