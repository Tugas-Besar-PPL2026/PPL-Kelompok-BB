<?php

namespace Tests\Browser\PBI08;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TC_PBI8_002_Test extends DuskTestCase
{
    public function test_jumlah_usaha_pada_tab_status(): void
    {
        $this->browse(function (Browser $browser) {
            $admin = User::where('email', 'admin@proyekkita.test')->firstOrFail();

            $browser->loginAs($admin)
                ->visit('/admin/verifikasi')
                ->assertSee('Menunggu')
                ->assertSee('2')
                ->assertSee('Terverifikasi')
                ->assertSee('1')
                ->assertSee('Ditolak')
                ->assertSee('1');
        });
    }
}