<?php

namespace Tests\Browser\Cerin\PBI24;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\KesepakatanDuskTestCase;

/**
 * TC-PBI24-006 — Hasil yang diserahkan kosong
 *
 * Jenis Test     : Negatif
 * Data disiapkan : Kandidat Diterima; draf kesepakatan sudah ada.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Muncul pesan "Hasil yang harus diserahkan wajib diisi." di bawah kolom; kesepakatan tetap Draf di tab Draf.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-24/TC_PBI24_006_Test.php
 */
class TC_PBI24_006_Test extends KesepakatanDuskTestCase
{
    public function test_tc_pbi24_006(): void
    {
        $this->browse(function (Browser $browser) {
            $d = $this->siapkanKesepakatan($browser, 'draf');

            // ===== Skenario utama =====
            $this->masuk($browser, $d['umkm']);
            $this->bukaDrafLewatMenu($browser, $d['kesepakatan']);
            $isi = $this->isiValid();
            $isi['deliverables'] = '';
            $this->isiKesepakatan($browser, $isi);
            $this->klikKirim($browser);
            $browser->assertPathIs("/umkm/kesepakatan/{$d['kesepakatan']}/susun")
                ->assertSeeIn('@galat-hasil-diserahkan', 'Hasil yang harus diserahkan wajib diisi.')
                ->assertSeeIn('@status-kesepakatan', 'Draf');
            $this->bukti($browser, 'TC_PBI24_006');

            $this->bukaMenuKesepakatan($browser, 'umkm');
            $this->assertSame(1, $this->jumlahTab($browser, 'draft'));
            $this->assertSame(0, $this->jumlahTab($browser, 'sent'));
        });
    }
}
