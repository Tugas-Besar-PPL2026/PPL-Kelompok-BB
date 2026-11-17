<?php

namespace Tests\Browser\Adnan\PBI06;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PBI06TestCase13Test extends DuskTestCase
{
    public function test_tc_pbi6_013_akses_ditolak_oleh_non_admin(): void
    {
        $emailUmkm = 'umkm.pbi6.' . date('His') . '@proyekkita.test';

        $this->browse(function (Browser $browser) use ($emailUmkm) {
            $browser->driver->manage()->deleteAllCookies();

            $browser->visit(route('register', ['role' => 'umkm']))
                ->type('name', 'UMKM Uji Akses')
                ->type('email', $emailUmkm)
                ->type('password', 'password123')
                ->type('password_confirmation', 'password123')
                ->check('terms')
                ->click('@daftar')
                ->pause(1000)
                ->visit('/admin/data-master')
                ->assertSee('403');
        });
    }
}