<?php

namespace Tests\Browser\Cerin\PBI25;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\KesepakatanDuskTestCase;

/**
 * TC-PBI25-007 — Kirim tanpa memilih tanggapan
 *
 * Jenis Test     : Negatif
 * Data disiapkan : Kesepakatan kandidat berstatus Dikirim.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Pesan "Pilih tanggapan terlebih dahulu."; status tetap Dikirim.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-25/TC_PBI25_007_Test.php
 */
class TC_PBI25_007_Test extends KesepakatanDuskTestCase
{
    public function test_tc_pbi25_007(): void
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
            $browser->assertRadioNotSelected('tanggapan', 'setujui')->assertRadioNotSelected('tanggapan', 'tolak');
            $this->klikKirimTanggapan($browser);
            $browser->assertSeeIn('@galat-tanggapan', 'Pilih tanggapan terlebih dahulu.')
                ->assertSeeIn('@status-kesepakatan', 'Menunggu Persetujuan Anda');
            $this->bukti($browser, 'TC_PBI25_007');
        });
    }
}
