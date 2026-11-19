<?php

namespace Tests\Browser\PBI01;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TC_PBI1_008_Test extends DuskTestCase
{
    public function test_nib_12_digit_ditolak(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::where('email', 'umkm@proyekkita.test')->firstOrFail();

            $browser->loginAs($user)
                ->visit('/umkm/profil')
                ->type('nib', '123456789012')
                ->press('Simpan Perubahan')
                ->assertSee('NIB harus terdiri dari 13 digit angka.');

            $this->assertDatabaseMissing('umkm_profiles', [
                'user_id' => $user->id,
                'nib'     => '123456789012',
            ]);
        });
    }
}