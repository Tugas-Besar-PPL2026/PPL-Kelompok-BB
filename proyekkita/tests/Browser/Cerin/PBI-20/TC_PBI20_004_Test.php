<?php

namespace Tests\Browser\Cerin\PBI20;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\CerinDuskTestCase;

/**
 * TC-PBI20-004 — Menolak pelamar dengan catatan valid
 *
 * Jenis Test     : Positif
 * Data disiapkan : 1 proyek terbit; K1 melamar lalu dimasukkan ke Terpilih Sementara.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Status menjadi Ditolak; catatan tersimpan; riwayat status baru tersimpan dengan waktu perubahan.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-20/TC_PBI20_004_Test.php
 */
class TC_PBI20_004_Test extends CerinDuskTestCase
{
    public function test_tc_pbi20_004(): void
    {
        $this->browse(function (Browser $browser) {
            $umkm = $this->siapkanUmkmTerverifikasi($browser, 'UMKM A');
            $proyek = $this->siapkanProyekTerbit($browser, $umkm, 'Proyek', 2);
            $k1 = $this->daftarKandidat($browser, 'K1');
            $this->lamar($browser, $k1, $proyek);
            $this->putuskan($browser, $umkm, $proyek, $k1, 'shortlist');

            // ===== Skenario utama =====
            $this->masuk($browser, $umkm);
            $this->bukaSeleksi($browser);
            $this->pilihProyek($browser, $proyek['id']);
            $lamaran = $this->lamaranIdDiTabel($browser, $k1['nama']);
            $this->lihatPelamar($browser, $lamaran);
            $riwayatAwal = (int) $browser->script("return document.querySelectorAll('.tl .it').length;")[0];

            $this->pilihKeputusan($browser, 'reject');
            $this->isiCatatan($browser, 'Belum sesuai kebutuhan');
            $this->simpanKeputusan($browser);

            $browser->assertSeeIn('.prof .bd', 'Ditolak')
                ->assertVisible('@status-final');
            $this->assertStringContainsString('Catatan seleksi tersimpan: Belum sesuai kebutuhan', $this->teksRapi($browser, '[dusk="panel-keputusan"]'));
            $this->assertSame($riwayatAwal + 1, (int) $browser->script("return document.querySelectorAll('.tl .it').length;")[0]);
            $this->assertStringContainsStringIgnoringCase('Ditolak', $browser->script("return (document.querySelector('.tl .it')?.innerText || '').replace(/\\s+/g, ' ').trim();")[0]);
            $this->bukti($browser, 'TC_PBI20_004');
        });
    }
}
