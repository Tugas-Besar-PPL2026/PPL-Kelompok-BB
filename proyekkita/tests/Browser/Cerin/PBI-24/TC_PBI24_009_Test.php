<?php

namespace Tests\Browser\Cerin\PBI24;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\KesepakatanDuskTestCase;

/**
 * TC-PBI24-009 — Batas revisi bukan bilangan bulat
 *
 * Jenis Test     : Negatif
 * Data disiapkan : Kandidat Diterima; draf kesepakatan sudah ada.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Pesan "Batas revisi harus berupa bilangan bulat."; kesepakatan tidak terkirim, status tetap Draf.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-24/TC_PBI24_009_Test.php
 */
class TC_PBI24_009_Test extends KesepakatanDuskTestCase
{
    public function test_tc_pbi24_009(): void
    {
        $this->browse(function (Browser $browser) {
            $d = $this->siapkanKesepakatan($browser, 'draf');

            // ===== Skenario utama =====
            $this->masuk($browser, $d['umkm']);
            $this->bukaDrafLewatMenu($browser, $d['kesepakatan']);
            $isi = $this->isiValid();
            $isi['revision_limit'] = '1,5';
            $this->isiKesepakatan($browser, $isi);
            $this->klikKirim($browser);
            $browser->assertPathIs("/umkm/kesepakatan/{$d['kesepakatan']}/susun")
                ->assertSeeIn('@galat-batas-revisi', 'Batas revisi harus berupa bilangan bulat.')
                ->assertSeeIn('@status-kesepakatan', 'Draf');
            $this->bukti($browser, 'TC_PBI24_009');
        });
    }
}
