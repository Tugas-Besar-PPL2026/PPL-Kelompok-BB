<?php

namespace Tests\Browser\PBI01;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TC_PBI1_002_Test extends DuskTestCase
{
    public function test_profil_usaha_belum_diisi(): void
    {
        $this->browse(function (Browser $browser) {
            $newUmkm = User::factory()->create(['role' => 'umkm']);

            $browser->loginAs($newUmkm)
                ->visit('/umkm/profil')
                ->assertPathIs('/umkm/profil')
                ->assertSee('Belum diisi')
                ->assertSee('Belum ada penilaian.');
        });
    }
}