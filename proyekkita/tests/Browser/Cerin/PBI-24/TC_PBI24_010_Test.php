<?php

namespace Tests\Browser\Cerin\PBI24;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\KesepakatanDuskTestCase;

/**
 * TC-PBI24-010 — Boundary - Batas revisi -1 dan 0
 *
 * Jenis Test     : Boundary
 * Data disiapkan : Kandidat Diterima; draf kesepakatan sudah ada.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : -1 ditolak dengan pesan "Batas revisi minimal 0." (tetap Draf); 0 diterima dan kesepakatan terkirim.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-24/TC_PBI24_010_Test.php
 */
class TC_PBI24_010_Test extends KesepakatanDuskTestCase
{
    public function test_tc_pbi24_010(): void
    {
        $this->browse(function (Browser $browser) {
            $d = $this->siapkanKesepakatan($browser, 'draf');

            // ===== Skenario utama =====
            $this->masuk($browser, $d['umkm']);
            $this->bukaDrafLewatMenu($browser, $d['kesepakatan']);

            // Nilai -1 (di bawah batas)
            $isi = $this->isiValid();
            $isi['revision_limit'] = '-1';
            $this->isiKesepakatan($browser, $isi);
            $this->klikKirim($browser);
            $browser->assertPathIs("/umkm/kesepakatan/{$d['kesepakatan']}/susun")
                ->assertSeeIn('@galat-batas-revisi', 'Batas revisi minimal 0.')
                ->assertSeeIn('@status-kesepakatan', 'Draf');
            $this->bukti($browser, 'TC_PBI24_010_minus1');

            // Nilai 0 (tepat di batas)
            $isi['revision_limit'] = '0';
            $this->isiKesepakatan($browser, $isi);
            $this->klikKirim($browser);
            $browser->assertSee('Kesepakatan berhasil dikirim ke kandidat.')
                ->assertSeeIn('@detail-kesepakatan @status-kesepakatan', 'Menunggu Persetujuan')
                ->assertSeeIn('@ringkasan-revisi', '0 kali');
            $this->bukti($browser, 'TC_PBI24_010_nol');
        });
    }
}
