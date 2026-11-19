<?php

namespace Tests\Browser\PBI08;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TC_PBI8_012_Test extends DuskTestCase
{
    public function test_menyetujui_umkm(): void
    {
        $this->browse(function (Browser $browser) {
            $admin = User::where('email', 'admin@proyekkita.test')->firstOrFail();

            $browser->loginAs($admin)
                ->visit('/admin/verifikasi')
                ->press('Setujui')
                ->assertSee('Akun UMKM berhasil diverifikasi.');
        });
    }
}