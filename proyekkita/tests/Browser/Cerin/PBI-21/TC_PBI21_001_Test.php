<?php

namespace Tests\Browser\Cerin\PBI21;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\CerinDuskTestCase;

/**
 * TC-PBI21-001 — Menerima pelamar saat kuota masih tersedia
 *
 * Jenis Test     : Positif
 * Data disiapkan : 1 proyek terbit kuota 3; K1 melamar (Menunggu Keputusan).
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Status menjadi Diterima; catatan dan riwayat tersimpan dengan waktu penerimaan; muncul 'Pelamar berhasil diterima.'
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-21/TC_PBI21_001_Test.php
 */
class TC_PBI21_001_Test extends CerinDuskTestCase
{
    public function test_tc_pbi21_001(): void
    {
        $this->browse(function (Browser $browser) {
            $umkm = $this->siapkanUmkmTerverifikasi($browser, 'UMKM A');
            $proyek = $this->siapkanProyekTerbit($browser, $umkm, 'Proyek', 3);
            $k1 = $this->daftarKandidat($browser, 'K1');
            $this->lamar($browser, $k1, $proyek);

            // ===== Skenario utama =====
            $this->masuk($browser, $umkm);
            $this->bukaSeleksi($browser);
            $this->pilihProyek($browser, $proyek['id']);
            $lamaran = $this->lamaranIdDiTabel($browser, $k1['nama']);
            $this->lihatPelamar($browser, $lamaran);
            $riwayatAwal = (int) $browser->script("return document.querySelectorAll('.tl .it').length;")[0];

            $this->pilihKeputusan($browser, 'accept');
            $this->isiCatatan($browser, 'Selamat bergabung');
            $this->simpanKeputusan($browser);

            $browser->assertSee('Pelamar berhasil diterima.')
                ->assertSeeIn('.prof .bd', 'Diterima');
            $this->assertStringContainsString('Catatan seleksi tersimpan: Selamat bergabung', $this->teksRapi($browser, '[dusk="panel-keputusan"]'));
            $this->assertSame($riwayatAwal + 1, (int) $browser->script("return document.querySelectorAll('.tl .it').length;")[0]);
            $this->assertStringContainsStringIgnoringCase('Diterima', $browser->script("return (document.querySelector('.tl .it')?.innerText || '').replace(/\\s+/g, ' ').trim();")[0]);
            $this->bukti($browser, 'TC_PBI21_001');
        });
    }
}
