<?php

namespace Tests\Browser\Cerin\PBI24;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\KesepakatanDuskTestCase;

/**
 * TC-PBI24-004 — Kirim kesepakatan lengkap
 *
 * Jenis Test     : Positif
 * Data disiapkan : Kandidat Diterima; draf kesepakatan sudah ada.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Pesan berhasil dikirim, status Menunggu Persetujuan (tab Dikirim), tanggal kirim tampil, kesepakatan pindah dari tab Draf ke Dikirim.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-24/TC_PBI24_004_Test.php
 */
class TC_PBI24_004_Test extends KesepakatanDuskTestCase
{
    public function test_tc_pbi24_004(): void
    {
        $this->browse(function (Browser $browser) {
            $d = $this->siapkanKesepakatan($browser, 'draf');

            // ===== Skenario utama =====
            $this->masuk($browser, $d['umkm']);
            $this->bukaDrafLewatMenu($browser, $d['kesepakatan']);
            $this->isiKesepakatan($browser, $this->isiValid());
            $this->klikKirim($browser);
            $browser->assertPathIs("/umkm/kesepakatan/{$d['kesepakatan']}")
                ->assertSee('Kesepakatan berhasil dikirim ke kandidat.')
                ->assertSeeIn('@detail-kesepakatan @status-kesepakatan', 'Menunggu Persetujuan')
                ->assertSeeIn('@status-detail', 'Dikirim ke kandidat');
            $this->assertNotSame('-', $this->teksRapi($browser, '[dusk="tanggal-kirim"]'));
            $this->assertSame(1, $this->jumlahTab($browser, 'sent'));
            $this->assertSame(0, $this->jumlahTab($browser, 'draft'));
            $this->bukti($browser, 'TC_PBI24_004');
        });
    }
}
