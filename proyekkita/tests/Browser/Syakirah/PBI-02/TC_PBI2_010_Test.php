<?php

namespace Tests\Browser\PBI02;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TC_PBI2_010_Test extends DuskTestCase
{
    public function test_menu_sidebar_dan_layout_kandidat(): void
    {
        $this->browse(function (Browser $browser) {
            $kandidat = User::where('email', 'kandidat@proyekkita.test')->firstOrFail();

            $browser->loginAs($kandidat)
                ->visit('/kandidat/profil/data-diri')
                ->assertPathIs('/kandidat/profil/data-diri')
                ->assertSee('Profil & Portofolio');
        });
    }
}