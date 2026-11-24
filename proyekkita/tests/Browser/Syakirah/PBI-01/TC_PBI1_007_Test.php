<?php

namespace Tests\Browser\PBI01;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TC_PBI1_007_Test extends DuskTestCase
{
    public function test_nib_dan_tautan_sama_sama_kosong(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::where('email', 'umkm@proyekkita.test')->firstOrFail();

            $browser->loginAs($user)
                ->visit('/umkm/profil')
                ->type('nib', '')
                ->type('business_link', '')
                ->press('Simpan Perubahan')
                ->assertSee('Isi NIB atau tautan informasi usaha.')
                ->assertSee('Isi tautan informasi usaha atau NIB.');
        });
    }
}