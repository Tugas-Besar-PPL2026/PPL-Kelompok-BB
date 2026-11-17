<?php

namespace Tests\Browser\Cerin\PBI21;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\CerinDuskTestCase;

/**
 * TC-PBI21-009 — Penerimaan tidak membuat kesepakatan kerja (PBI-24)
 *
 * Jenis Test     : Positif
 * Data disiapkan : 1 proyek terbit kuota 2; K1 melamar (Menunggu Keputusan).
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Tidak ada kesepakatan yang dibuat otomatis.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-21/TC_PBI21_009_Test.php
 */
class TC_PBI21_009_Test extends CerinDuskTestCase
{
    public function test_tc_pbi21_009(): void
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

            $this->pilihKeputusan($browser, 'accept');
            $this->simpanKeputusan($browser);
            $browser->assertSee('Pelamar berhasil diterima.');
            $this->bukti($browser, 'TC_PBI21_009');

            // Periksa tabel agreements di database (baca saja, diizinkan khusus TC ini)
            $this->assertSame(0, DB::table('agreements')->where('application_id', $lamaran)->count());
        });
    }
}
