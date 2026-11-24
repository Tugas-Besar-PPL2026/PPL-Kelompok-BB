<?php

namespace Tests\Browser\PBI08;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TC_PBI8_013_Test extends DuskTestCase
{
    public function test_menolak_umkm_dengan_alasan(): void
    {
        $this->browse(function (Browser $browser) {
            $admin = User::where('email', 'admin@proyekkita.test')->firstOrFail();

            $browser->loginAs($admin)
                ->visit('/admin/verifikasi')
                ->type('rejection_reason', '  NIB tidak sesuai dengan nama usaha.  ')
                ->press('Tolak')
                ->assertSee('Verifikasi UMKM ditolak.');

            $this->assertDatabaseHas('umkm_profiles', [
                'rejection_reason' => 'NIB tidak sesuai dengan nama usaha.',
                'verified_at'      => null,
            ]);
        });
    }
}