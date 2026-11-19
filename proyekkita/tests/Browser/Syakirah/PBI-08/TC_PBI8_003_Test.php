<?php

namespace Tests\Browser\PBI08;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TC_PBI8_003_Test extends DuskTestCase
{
    public function test_filter_antrean_per_status(): void
    {
        $this->browse(function (Browser $browser) {
            $admin = User::where('email', 'admin@proyekkita.test')->firstOrFail();

            $browser->loginAs($admin)
                ->visit('/admin/verifikasi')
                ->clickLink('Terverifikasi')
                ->assertSee('Terverifikasi')
                ->clickLink('Ditolak')
                ->assertSee('Ditolak')
                ->clickLink('Menunggu')
                ->assertSee('Menunggu');
        });
    }
}