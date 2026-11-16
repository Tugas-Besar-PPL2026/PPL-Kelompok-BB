<?php

namespace Tests\Browser\Cerin\PBI20;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\CerinDuskTestCase;

/**
 * TC-PBI20-008 — Form keputusan tampil aktif pada lamaran yang belum final
 *
 * Jenis Test     : Positif
 * Data disiapkan : 1 proyek terbit; K1 melamar (Menunggu Keputusan).
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Panel Keputusan Seleksi menampilkan pilihan, catatan, dan tombol Simpan Keputusan yang aktif.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-20/TC_PBI20_008_Test.php
 */
class TC_PBI20_008_Test extends CerinDuskTestCase
{
    public function test_tc_pbi20_008(): void
    {
        $this->browse(function (Browser $browser) {
            $umkm = $this->siapkanUmkmTerverifikasi($browser, 'UMKM A');
            $proyek = $this->siapkanProyekTerbit($browser, $umkm, 'Proyek', 2);
            $k1 = $this->daftarKandidat($browser, 'K1');
            $this->lamar($browser, $k1, $proyek);

            // ===== Skenario utama =====
            $this->masuk($browser, $umkm);
            $this->bukaSeleksi($browser);
            $this->pilihProyek($browser, $proyek['id']);
            $lamaran = $this->lamaranIdDiTabel($browser, $k1['nama']);
            $this->lihatPelamar($browser, $lamaran);

            $browser->assertVisible('@panel-keputusan')
                ->assertSeeIn('@panel-keputusan', 'Keputusan Seleksi')
                ->assertPresent('@opsi-shortlist')
                ->assertPresent('@opsi-accept')
                ->assertPresent('@opsi-reject')
                ->assertVisible('@catatan-seleksi')
                ->assertVisible('@simpan-keputusan')
                ->assertMissing('@status-final');
            $this->assertTrue((bool) $browser->script("return ! document.querySelector('[dusk=\"simpan-keputusan\"]').disabled;")[0]);
            $this->assertGreaterThan(0, (int) $browser->script("return document.querySelectorAll('[dusk=\"panel-keputusan\"] input[name=\"keputusan\"]:not(:disabled)').length;")[0]);
            $this->bukti($browser, 'TC_PBI20_008');
        });
    }
}
