<?php

namespace Tests\Browser\Cerin\PBI19;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\CerinDuskTestCase;

/**
 * TC-PBI19-005 — Berganti proyek melalui dropdown dan hanya proyek milik sendiri yang tersedia
 *
 * Jenis Test     : Positif
 * Data disiapkan : UMKM A punya 2 proyek terbit (masing-masing dilamar K1); UMKM B terverifikasi dan punya 1 proyek terbit.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Statistik, kuota, dan tabel berubah sesuai proyek terpilih; proyek UMKM lain tidak tersedia.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-19/TC_PBI19_005_Test.php
 */
class TC_PBI19_005_Test extends CerinDuskTestCase
{
    public function test_tc_pbi19_005(): void
    {
        $this->browse(function (Browser $browser) {
            $umkm = $this->siapkanUmkmTerverifikasi($browser, 'UMKM A');
            $proyekA1 = $this->siapkanProyekTerbit($browser, $umkm, 'Proyek A1', 2);
            $proyekA2 = $this->siapkanProyekTerbit($browser, $umkm, 'Proyek A2', 3);
            $umkmB = $this->siapkanUmkmTerverifikasi($browser, 'UMKM B');
            $proyekB = $this->siapkanProyekTerbit($browser, $umkmB, 'Proyek B', 1);
            $k1 = $this->daftarKandidat($browser, 'K1');
            $this->lamar($browser, $k1, $proyekA1);
            $this->lamar($browser, $k1, $proyekA2);

            // ===== Skenario utama =====
            $this->masuk($browser, $umkm);
            $this->bukaSeleksi($browser);
            $this->pilihProyek($browser, $proyekA1['id']);

            // Pilih proyek lain pada pilihan proyek
            $this->pilihProyek($browser, $proyekA2['id']);
            $this->assertSame(['1', '1', '0', '0 / 3'], $this->statistik($browser));
            $browser->assertSeeIn('@kuota-daftar', 'Kuota kandidat terisi 0 dari 3');
            $this->assertSame([$k1['nama']], $this->namaDiTabel($browser));

            // Pilihan proyek hanya berisi proyek milik UMKM A
            $opsi = $browser->script("return [...document.querySelectorAll('#pilih-proyek option')].map(o => ({v: o.value, t: o.textContent.trim()}));")[0];
            $this->assertEqualsCanonicalizing([(string) $proyekA1['id'], (string) $proyekA2['id']], array_column($opsi, 'v'));
            $this->assertNotContains($proyekB['judul'], array_column($opsi, 't'));
            $this->bukti($browser, 'TC_PBI19_005');
        });
    }
}
