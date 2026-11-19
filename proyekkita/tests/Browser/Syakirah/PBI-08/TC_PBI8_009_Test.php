<?php

namespace Tests\Browser\PBI08;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TC_PBI8_028_Test extends DuskTestCase
{
    public function test_tampilan_halaman_dan_menu_sidebar_admin(): void
    {
        $this->browse(function (Browser $browser) {
            $admin = User::where('email', 'admin@proyekkita.test')->firstOrFail();

            $browser->loginAs($admin)
                ->visit('/admin/verifikasi')
                ->assertPathIs('/admin/verifikasi')
                ->assertSee('Verifikasi UMKM');
        });
    }
}