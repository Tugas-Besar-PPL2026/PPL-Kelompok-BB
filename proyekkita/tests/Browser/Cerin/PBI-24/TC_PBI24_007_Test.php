<?php

namespace Tests\Browser\Cerin\PBI24;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\KesepakatanDuskTestCase;

/**
 * TC-PBI24-007 — Tenggat kosong
 *
 * Jenis Test     : Negatif
 * Data disiapkan : Kandidat Diterima; draf kesepakatan sudah ada.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Muncul pesan "Tenggat penyelesaian wajib diisi." di bawah kolom; kesepakatan tetap Draf di tab Draf.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-24/TC_PBI24_007_Test.php
 */
class TC_PBI24_007_Test extends KesepakatanDuskTestCase
{
    public function test_tc_pbi24_007(): void
    {
        $this->browse(function (Browser $browser) {
            $d = $this->siapkanKesepakatan($browser, 'draf');

            // ===== Skenario utama =====
            $this->masuk($browser, $d['umkm']);
            $this->bukaDrafLewatMenu($browser, $d['kesepakatan']);
            $isi = $this->isiValid();
            $isi['deadline'] = '';
            $this->isiKesepakatan($browser, $isi);
            $this->klikKirim($browser);
            $browser->assertPathIs("/umkm/kesepakatan/{$d['kesepakatan']}/susun")
                ->assertSeeIn('@galat-tenggat', 'Tenggat penyelesaian wajib diisi.')
                ->assertSeeIn('@status-kesepakatan', 'Draf');
            $this->bukti($browser, 'TC_PBI24_007');

            $this->bukaMenuKesepakatan($browser, 'umkm');
            $this->assertSame(1, $this->jumlahTab($browser, 'draft'));
            $this->assertSame(0, $this->jumlahTab($browser, 'sent'));
        });
    }
}
