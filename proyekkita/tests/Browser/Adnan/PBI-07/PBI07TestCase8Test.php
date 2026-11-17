<?php

namespace Tests\Browser\Adnan\PBI07;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PBI07TestCase8Test extends DuskTestCase
{
    public function test_tc_pbi7_008_batas_panjang_nama_maksimal(): void
    {
        $teks100 = str_pad('Skill100_' . date('His') . '_', 100, 'x');
        $teks101 = str_pad('Skill101_' . date('His') . '_', 101, 'y');

        $this->browse(function (Browser $browser) use ($teks100, $teks101) {
            $browser->visit('/login')
                ->type('email', 'admin@proyekkita.test')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->type('password', 'password')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->press('Masuk')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->waitForLocation('/admin/data-master')
                ->pause((int) env('DUSK_JEDA', 1000))
                // 1. Uji 100 karakter (Valid)
                ->type('@input-keterampilan-baru', $teks100)
                ->pause((int) env('DUSK_JEDA', 1000))
                ->click('@tambah-keterampilan')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->pause((int) env('DUSK_JEDA', 1000))
                ->assertSeeIn('@kartu-keterampilan', $teks100);

            // 2. Uji 101 karakter (Hapus maxlength sementara agar bisa kirim 101 karakter ke server)
            $browser->script("document.getElementById('skill_name').removeAttribute('maxlength');");
            $browser->type('@input-keterampilan-baru', $teks101)
                ->pause((int) env('DUSK_JEDA', 1000))
                ->click('@tambah-keterampilan')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->pause((int) env('DUSK_JEDA', 1000))
                ->assertVisible('@galat-tambah-keterampilan')
                ->assertSeeIn('@galat-tambah-keterampilan', '100');
        });

        $this->assertDatabaseHas('skills', ['name' => $teks100]);
        $this->assertDatabaseMissing('skills', ['name' => $teks101]);
    }
}