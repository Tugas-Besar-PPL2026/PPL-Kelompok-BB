<?php

namespace Tests\Browser\Cerin\PBI25;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\KesepakatanDuskTestCase;

/**
 * TC-PBI25-006 — Boundary - Alasan satu karakter
 *
 * Jenis Test     : Boundary
 * Data disiapkan : Kesepakatan kandidat berstatus Dikirim.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Alasan "x" diterima: status Ditolak dan alasan "x" tampil di detail.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-25/TC_PBI25_006_Test.php
 */
class TC_PBI25_006_Test extends KesepakatanDuskTestCase
{
    public function test_tc_pbi25_006(): void
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
            $this->isiTanggapan($browser, 'tolak', 'x');
            $this->klikKirimTanggapan($browser);
            $browser->assertSee('Tanggapan berhasil dikirim.')
                ->assertSeeIn('@detail-kesepakatan @status-kesepakatan', 'Ditolak');
            $this->assertSame('Alasan: x', $this->teksRapi($browser, '[dusk="alasan-penolakan"]'));
            $this->bukti($browser, 'TC_PBI25_006');
        });
    }
}
