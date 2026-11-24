<?php

namespace Tests\Browser\Cerin\PBI24;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\KesepakatanDuskTestCase;

/**
 * TC-PBI24-015 — Kesepakatan ganda untuk satu lamaran
 *
 * Jenis Test     : Negatif
 * Data disiapkan : Kandidat Diterima dan belum memiliki kesepakatan. Dua jendela browser (A dan B) login sebagai UMKM yang sama.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Jendela A tidak membuat kesepakatan kedua, muncul pesan "Kesepakatan untuk lamaran ini sudah ada."; tab Draf tetap 1.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-24/TC_PBI24_015_Test.php
 */
class TC_PBI24_015_Test extends KesepakatanDuskTestCase
{
    public function test_tc_pbi24_015(): void
    {
        $this->browse(function (Browser $jendelaA, Browser $jendelaB) {
            $d = $this->siapkanPelamarDiterima($jendelaA);

            // ===== Skenario utama =====
            foreach ([$jendelaA, $jendelaB] as $jendela) {
                $this->masuk($jendela, $d['umkm']);
                $this->bukaSeleksi($jendela);
                $this->pilihProyek($jendela, $d['proyek']['id']);
                $this->lihatPelamar($jendela, $d['lamaran']);
                $jendela->assertPresent('@susun-kesepakatan');
            }

            // Jendela B menyusun lebih dulu
            $jendelaB->click('@susun-kesepakatan')->waitFor('@form-kesepakatan', 20);
            $this->bukti($jendelaB, 'TC_PBI24_015_jendelaB');

            // Jendela A (belum dimuat ulang) menekan tombol yang sama
            $jendelaA->click('@susun-kesepakatan')
                ->waitForText('Kesepakatan untuk lamaran ini sudah ada.', 20);
            $this->bukti($jendelaA, 'TC_PBI24_015_jendelaA');

            $this->bukaMenuKesepakatan($jendelaA, 'umkm');
            $this->klikTab($jendelaA, 'draft');
            $this->assertSame(1, $this->jumlahTab($jendelaA, 'draft'));
            $this->assertSame(1, (int) $jendelaA->script("return document.querySelectorAll('table.ks-table tbody tr').length;")[0]);
        });
    }
}
