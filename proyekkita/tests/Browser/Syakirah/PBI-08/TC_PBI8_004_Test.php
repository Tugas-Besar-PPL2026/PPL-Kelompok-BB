<?php

namespace Tests\Browser\PBI08;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TC_PBI8_004_Test extends DuskTestCase
{
    public function test_tab_ditolak_menampilkan_alasan_tanpa_form_keputusan(): void
    {
        $this->browse(function (Browser $browser) {
            $admin = User::where('email', 'admin@proyekkita.test')->firstOrFail();

            $browser->loginAs($admin)
                ->visit('/admin/verifikasi?status=rejected')
                ->assertSee('Ditolak')
                ->assertDontSee('Setujui');
        });
    }
}