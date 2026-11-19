<?php

namespace Tests\Browser\PBI08;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TC_PBI8_014_Test extends DuskTestCase
{
    public function test_menolak_umkm_tanpa_alasan(): void
    {
        $this->browse(function (Browser $browser) {
            $admin = User::where('email', 'admin@proyekkita.test')->firstOrFail();

            $browser->loginAs($admin)
                ->visit('/admin/verifikasi')
                ->type('rejection_reason', '')
                ->press('Tolak')
                ->assertSee('Alasan penolakan wajib diisi.');
        });
    }
}