<?php

namespace Tests\Browser\Adnan\PBI07;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PBI07TestCase2Test extends DuskTestCase
{
    public function test_tc_pbi7_002_tambah_keterampilan_valid(): void
    {
        $namaSkill = 'Adobe Illustrator ' . date('His');

        $this->browse(function (Browser $browser) use ($namaSkill) {
            $browser->visit('/login')
                ->type('email', 'admin@proyekkita.test')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->type('password', 'password')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->press('Masuk')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->waitForLocation('/admin/data-master')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->type('@input-keterampilan-baru', $namaSkill)
                ->pause((int) env('DUSK_JEDA', 1000))
                ->click('@tambah-keterampilan')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->pause((int) env('DUSK_JEDA', 1000))
                ->assertSeeIn('@kartu-keterampilan', $namaSkill);
        });

        $this->assertDatabaseHas('skills', [
            'name' => $namaSkill,
            'is_active' => 1,
        ]);
    }
}