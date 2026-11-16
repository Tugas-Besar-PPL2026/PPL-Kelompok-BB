<?php

namespace Tests\Browser\Cerin\PBI20;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\CerinDuskTestCase;

/**
 * TC-PBI20-001 — Memasukkan pelamar ke Terpilih Sementara tanpa catatan
 *
 * Jenis Test     : Positif
 * Data disiapkan : 1 proyek terbit; K1 melamar (Menunggu Keputusan).
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Status menjadi Terpilih Sementara, muncul pesan sukses, dan satu riwayat status baru tersimpan dengan waktu perubahan.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-20/TC_PBI20_001_Test.php
 */
class TC_PBI20_001_Test extends CerinDuskTestCase
{
    public function test_tc_pbi20_001(): void
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
            $riwayatAwal = (int) $browser->script("return document.querySelectorAll('.tl .it').length;")[0];

            $this->pilihKeputusan($browser, 'shortlist');
            $this->isiCatatan($browser, '');
            $this->simpanKeputusan($browser);

            $browser->assertSee('Pelamar dimasukkan ke daftar terpilih sementara.')
                ->assertSeeIn('.prof .bd', 'Terpilih Sementara');
            $this->assertSame($riwayatAwal + 1, (int) $browser->script("return document.querySelectorAll('.tl .it').length;")[0]);
            $this->assertStringContainsStringIgnoringCase('Terpilih Sementara', $browser->script("return (document.querySelector('.tl .it')?.innerText || '').replace(/\\s+/g, ' ').trim();")[0]);
            $this->bukti($browser, 'TC_PBI20_001');
        });
    }
}
