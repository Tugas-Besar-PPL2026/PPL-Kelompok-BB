<?php

namespace Tests\Browser\PBI02;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TC_PBI2_009_Test extends DuskTestCase
{
    public function test_akses_data_diri_kandidat_oleh_umkm_ditolak_403(): void
    {
        $this->browse(function (Browser $browser) {
            $umkm = User::where('email', 'umkm@proyekkita.test')->firstOrFail();

            $browser->loginAs($umkm)
                ->visit('/kandidat/profil/data-diri')
                ->assertSee('403');
        });
    }
}