<?php

namespace Tests\Browser\Cerin\PBI25;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\KesepakatanDuskTestCase;

/**
 * TC-PBI25-009 — Setujui dengan alasan terisi
 *
 * Jenis Test     : Positif
 * Data disiapkan : Kesepakatan kandidat berstatus Dikirim.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Alasan yang sempat diisi diabaikan: status Aktif dan tidak ada alasan penolakan di detail.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-25/TC_PBI25_009_Test.php
 */
class TC_PBI25_009_Test extends KesepakatanDuskTestCase
{
    public function test_tc_pbi25_009(): void
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
            $this->isiTanggapan($browser, 'tolak', 'coba');
            $this->isiTanggapan($browser, 'setujui', 'coba', true);
            $this->klikKirimTanggapan($browser);
            $browser->assertSee('Tanggapan berhasil dikirim.')
                ->assertSeeIn('@detail-kesepakatan @status-kesepakatan', 'Aktif')
                ->assertMissing('@alasan-penolakan')
                ->assertMissing('@info-ditolak');
            $this->bukti($browser, 'TC_PBI25_009');
        });
    }
}
