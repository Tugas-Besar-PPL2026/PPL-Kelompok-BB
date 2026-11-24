<?php

namespace Tests\Browser\Cerin\PBI25;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\KesepakatanDuskTestCase;

/**
 * TC-PBI25-002 — Menyetujui kesepakatan
 *
 * Jenis Test     : Positif
 * Data disiapkan : Kesepakatan kandidat berstatus Dikirim.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Pesan "Tanggapan berhasil dikirim.", status Aktif, tanggal disetujui tampil, pindah dari tab Perlu Tanggapan ke Aktif.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-25/TC_PBI25_002_Test.php
 */
class TC_PBI25_002_Test extends KesepakatanDuskTestCase
{
    public function test_tc_pbi25_002(): void
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
            $this->isiTanggapan($browser, 'setujui', '', true);
            $this->klikKirimTanggapan($browser);
            $browser->assertPathIs("/kandidat/kesepakatan/{$d['kesepakatan']}")
                ->assertSee('Tanggapan berhasil dikirim.')
                ->assertSeeIn('@detail-kesepakatan @status-kesepakatan', 'Aktif')
                ->assertSeeIn('@status-detail', 'Disetujui kandidat');
            $this->assertNotSame('-', $this->teksRapi($browser, '[dusk="tanggal-disetujui"]'));
            $this->assertSame(1, $this->jumlahTab($browser, 'active'));
            $this->assertSame(0, $this->jumlahTab($browser, 'sent'));
            $this->bukti($browser, 'TC_PBI25_002');
        });
    }
}
