<?php

namespace Tests\Browser\Cerin\PBI25;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\KesepakatanDuskTestCase;

/**
 * TC-PBI25-003 — Menolak dengan alasan
 *
 * Jenis Test     : Positif
 * Data disiapkan : Kesepakatan kandidat berstatus Dikirim.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Status Ditolak; alasan "Tenggat terlalu singkat untuk 3 SKU" tampil di detail; pindah ke tab Ditolak/Dibatalkan; UMKM melihat alasan yang sama.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-25/TC_PBI25_003_Test.php
 */
class TC_PBI25_003_Test extends KesepakatanDuskTestCase
{
    public function test_tc_pbi25_003(): void
    {
        $this->browse(function (Browser $browser) {
            $d = $this->siapkanKesepakatan($browser, 'dikirim');
            $alasan = 'Tenggat terlalu singkat untuk 3 SKU';

            // ===== Skenario utama =====
            $this->masuk($browser, $d['kandidat']);
            $this->bukaMenuKesepakatan($browser, 'kandidat');
            $this->klikTab($browser, 'sent');
            $browser->click("@kartu-kesepakatan-{$d['kesepakatan']}")
                ->waitForLocation("/kandidat/kesepakatan/{$d['kesepakatan']}/tanggapi", 20)
                ->waitFor('@form-tanggapan', 20);
            $this->isiTanggapan($browser, 'tolak', $alasan);
            $this->klikKirimTanggapan($browser);
            $browser->assertSee('Tanggapan berhasil dikirim.')
                ->assertSeeIn('@detail-kesepakatan @status-kesepakatan', 'Ditolak')
                ->assertSeeIn('@alasan-penolakan', $alasan);
            $this->assertSame(1, $this->jumlahTab($browser, 'closed'));
            $this->assertSame(0, $this->jumlahTab($browser, 'sent'));
            $this->bukti($browser, 'TC_PBI25_003');

            // UMKM dapat mengetahui penolakan beserta alasannya
            $this->masuk($browser, $d['umkm']);
            $this->bukaMenuKesepakatan($browser, 'umkm');
            $this->klikTab($browser, 'closed');
            $browser->click("@buka-kesepakatan-{$d['kesepakatan']}")
                ->waitFor('@detail-kesepakatan', 20)
                ->assertSeeIn('@alasan-penolakan', $alasan);
            $this->bukti($browser, 'TC_PBI25_003_umkm');
        });
    }
}
