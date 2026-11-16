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
                ->type('password', 'password')
                ->press('Masuk')
                ->waitForLocation('/admin/data-master')
                ->type('@input-keterampilan-baru', $namaSkill)
                ->click('@tambah-keterampilan')
                ->pause(500)
                ->assertSeeIn('@kartu-keterampilan', $namaSkill);
        });

        $this->assertDatabaseHas('skills', [
            'name' => $namaSkill,
            'is_active' => 1,
        ]);
    }
}