<?php

namespace Tests\Browser\Cerin\PBI26;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\KesepakatanDuskTestCase;

/**
 * TC-PBI26-012 — Kandidat membuka detail milik kandidat lain
 *
 * Jenis Test     : Negatif
 * Data disiapkan : Kesepakatan Kandidat 1 berstatus Aktif; akun Kandidat 2 terdaftar.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Kandidat 2 membuka URL detail milik Kandidat 1: halaman 403, data tidak tampil.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-26/TC_PBI26_012_Test.php
 */
class TC_PBI26_012_Test extends KesepakatanDuskTestCase
{
    public function test_tc_pbi26_012(): void
    {
        $this->browse(function (Browser $browser) {
            $d = $this->siapkanKesepakatan($browser, 'aktif');
            $k2 = $this->daftarKandidat($browser, 'K2');

            // ===== Skenario utama =====
            $this->masuk($browser, $k2);
            $browser->visit("/kandidat/kesepakatan/{$d['kesepakatan']}")
                ->assertSee('403')
                ->assertMissing('@detail-kesepakatan')
                ->assertDontSee(self::RUANG_LINGKUP);
            $this->bukti($browser, 'TC_PBI26_012');
        });
    }
}
