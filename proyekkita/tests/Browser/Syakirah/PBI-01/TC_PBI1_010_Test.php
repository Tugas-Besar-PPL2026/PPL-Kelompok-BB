<?php

namespace Tests\Browser\PBI01;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TC_PBI1_010_Test extends DuskTestCase
{
    public function test_menu_sidebar_profil_usaha_aktif(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::where('email', 'umkm@proyekkita.test')->firstOrFail();

            $browser->loginAs($user)
                ->visit('/umkm/profil')
                ->clickLink('Profil Usaha')
                ->assertPathIs('/umkm/profil')
                ->assertSee('Profil Usaha');
        });
    }
}