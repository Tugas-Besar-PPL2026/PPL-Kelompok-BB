<?php

namespace Tests\Browser\Adnan\PBI07;

use App\Models\Skill;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PBI07TestCase5Test extends DuskTestCase
{
    public function test_tc_pbi7_005_tambah_nama_duplikat(): void
    {
        $jumlahAwal = Skill::count();
        $skillAktif = Skill::where('is_active', true)->firstOrFail();

        $this->browse(function (Browser $browser) use ($skillAktif) {
            $browser->visit('/login')
                ->type('email', 'admin@proyekkita.test')
                ->type('password', 'password')
                ->press('Masuk')
                ->waitForLocation('/admin/data-master')
                ->type('@input-keterampilan-baru', $skillAktif->name)
                ->assertVisible('@galat-tambah-keterampilan')
                ->assertSeeIn('@galat-tambah-keterampilan', 'sudah terdaftar')
                ->assertDisabled('@tambah-keterampilan');
        });

        $this->assertEquals($jumlahAwal, Skill::count());
    }
}