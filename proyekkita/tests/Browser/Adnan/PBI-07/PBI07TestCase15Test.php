<?php

namespace Tests\Browser\Adnan\PBI07;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PBI07TestCase15Test extends DuskTestCase
{
    public function test_tc_pbi7_015_akses_ditolak_oleh_non_admin(): void
    {
        $emailUmkm = 'umkm.pbi7.' . date('His') . '@proyekkita.test';

        $this->browse(function (Browser $browser) use ($emailUmkm) {
            $browser->driver->manage()->deleteAllCookies();

            $browser->visit(route('register', ['role' => 'umkm']))
                ->type('name', 'UMKM Uji Akses PBI7')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->type('email', $emailUmkm)
                ->pause((int) env('DUSK_JEDA', 1000))
                ->type('password', 'password123')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->type('password_confirmation', 'password123')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->check('terms')
                ->click('@daftar')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->visit('/admin/data-master')
                ->assertSee('403');
        });
    }
}