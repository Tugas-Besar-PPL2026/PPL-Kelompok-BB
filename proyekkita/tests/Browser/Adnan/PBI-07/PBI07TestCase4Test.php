<?php

namespace Tests\Browser\Adnan\PBI07;

use App\Models\Skill;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PBI07TestCase4Test extends DuskTestCase
{
    public function test_tc_pbi7_004_tambah_keterampilan_nama_kosong(): void
    {
        $jumlahAwal = Skill::count();

        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->type('email', 'admin@proyekkita.test')
                ->type('password', 'password')
                ->press('Masuk')
                ->waitForLocation('/admin/data-master')
                ->type('@input-keterampilan-baru', '   ')
                ->assertDisabled('@tambah-keterampilan');

            // Uji validasi server dengan membuka disabled & required lewat JS
            $browser->script("
                const inp = document.getElementById('skill_name');
                inp.removeAttribute('required');
                document.getElementById('skill_add_btn').disabled = false;
            ");

            $browser->click('@tambah-keterampilan')
                ->pause(500)
                ->assertVisible('@galat-tambah-keterampilan');
        });

        $this->assertEquals($jumlahAwal, Skill::count());
    }
}