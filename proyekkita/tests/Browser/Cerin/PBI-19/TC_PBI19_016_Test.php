<?php

namespace Tests\Browser\Cerin\PBI19;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\CerinDuskTestCase;

/**
 * TC-PBI19-016 — Membuka detail tidak mengubah status lamaran
 *
 * Jenis Test     : Positif
 * Data disiapkan : 1 proyek terbit; K1 melamar (Menunggu Keputusan).
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Status tetap Menunggu Keputusan.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-19/TC_PBI19_016_Test.php
 */
class TC_PBI19_016_Test extends CerinDuskTestCase
{
    public function test_tc_pbi19_016(): void
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
            $browser->assertSeeIn('.prof .bd', 'Menunggu Keputusan');
            $this->bukti($browser, 'TC_PBI19_016');

            // Periksa status lamaran di database (baca saja, diizinkan khusus TC ini)
            $this->assertSame('pending', (string) DB::table('applications')->where('id', $lamaran)->value('status'));
        });
    }
}
