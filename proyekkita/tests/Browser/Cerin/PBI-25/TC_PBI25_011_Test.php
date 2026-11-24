<?php

namespace Tests\Browser\Cerin\PBI25;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\KesepakatanDuskTestCase;

/**
 * TC-PBI25-011 — Akses oleh kandidat lain
 *
 * Jenis Test     : Negatif
 * Data disiapkan : Kesepakatan K1 berstatus Dikirim; ada akun kandidat lain (K2) yang tidak terkait.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : K2 membuka URL tanggapan milik K1: halaman 403, data tidak tampil.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-25/TC_PBI25_011_Test.php
 */
class TC_PBI25_011_Test extends KesepakatanDuskTestCase
{
    public function test_tc_pbi25_011(): void
    {
        $this->browse(function (Browser $browser) {
            $d = $this->siapkanKesepakatan($browser, 'dikirim');
            $k2 = $this->daftarKandidat($browser, 'K2');

            // ===== Skenario utama =====
            $this->masuk($browser, $k2);
            $browser->visit("/kandidat/kesepakatan/{$d['kesepakatan']}/tanggapi")
                ->assertSee('403')
                ->assertMissing('@form-tanggapan')
                ->assertDontSee(self::RUANG_LINGKUP);
            $this->bukti($browser, 'TC_PBI25_011');
        });
    }
}
