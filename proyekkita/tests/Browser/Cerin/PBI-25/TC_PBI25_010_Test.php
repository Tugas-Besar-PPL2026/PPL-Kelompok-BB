<?php

namespace Tests\Browser\Cerin\PBI25;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\KesepakatanDuskTestCase;

/**
 * TC-PBI25-010 — Akses oleh UMKM
 *
 * Jenis Test     : Negatif
 * Data disiapkan : Kesepakatan kandidat berstatus Dikirim.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : UMKM membuka URL tanggapan kandidat: halaman 403; kesepakatan tetap di tab Dikirim.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-25/TC_PBI25_010_Test.php
 */
class TC_PBI25_010_Test extends KesepakatanDuskTestCase
{
    public function test_tc_pbi25_010(): void
    {
        $this->browse(function (Browser $browser) {
            $d = $this->siapkanKesepakatan($browser, 'dikirim');

            // ===== Skenario utama =====
            $this->masuk($browser, $d['umkm']);
            $browser->visit("/kandidat/kesepakatan/{$d['kesepakatan']}/tanggapi")
                ->assertSee('403')
                ->assertMissing('@form-tanggapan');
            $this->bukti($browser, 'TC_PBI25_010');

            $browser->visit('/umkm/profil');
            $this->bukaMenuKesepakatan($browser, 'umkm');
            $this->assertSame(1, $this->jumlahTab($browser, 'sent'));
        });
    }
}
