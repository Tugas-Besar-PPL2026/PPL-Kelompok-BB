<?php

namespace Tests\Browser\Cerin\PBI25;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\KesepakatanDuskTestCase;

/**
 * TC-PBI25-008 — Setujui tanpa centang pemahaman
 *
 * Jenis Test     : Negatif
 * Data disiapkan : Kesepakatan kandidat berstatus Dikirim.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Pesan "Centang pernyataan pemahaman terlebih dahulu."; status tetap Dikirim.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-25/TC_PBI25_008_Test.php
 */
class TC_PBI25_008_Test extends KesepakatanDuskTestCase
{
    public function test_tc_pbi25_008(): void
    {
        $this->browse(function (Browser $browser) {
            $d = $this->siapkanKesepakatan($browser, 'dikirim');

            // ===== Skenario utama =====
            $this->masuk($browser, $d['kandidat']);
            $this->bukaMenuKesepakatan($browser, 'kandidat');
            $this->klikTab($browser, 'sent');
            $browser->click("@kartu-kesepakatan-{$d['kesepakatan']}")
                ->waitForLocation("/kandidat/kesepakatan/{$d['kesepakatan']}/tanggapi", 20)
                ->waitFor('@form-tanggapan', 20);
            $this->isiTanggapan($browser, 'setujui', '', false);
            $this->klikKirimTanggapan($browser);
            $browser->assertSeeIn('@galat-pemahaman', 'Centang pernyataan pemahaman terlebih dahulu.')
                ->assertSeeIn('@status-kesepakatan', 'Menunggu Persetujuan Anda');
            $this->bukti($browser, 'TC_PBI25_008');
        });
    }
}
