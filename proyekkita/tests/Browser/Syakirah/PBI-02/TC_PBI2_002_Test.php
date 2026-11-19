<?php

namespace Tests\Browser\PBI02;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TC_PBI2_002_Test extends DuskTestCase
{
    public function test_tab_data_diri_tanpa_profil(): void
    {
        $this->browse(function (Browser $browser) {
            $newKandidat = User::factory()->create(['role' => 'kandidat']);

            $browser->loginAs($newKandidat)
                ->visit('/kandidat/profil/data-diri')
                ->assertPathIs('/kandidat/profil/data-diri')
                ->assertInputValue('headline', '')
                ->assertInputValue('university', '');
        });
    }
}