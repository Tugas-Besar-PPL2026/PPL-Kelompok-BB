<?php

namespace Tests\Browser\Cerin\PBI20;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\CerinDuskTestCase;

/**
 * TC-PBI20-009 — Panel keputusan terkunci pada lamaran berstatus final
 *
 * Jenis Test     : Negatif
 * Data disiapkan : 1 proyek terbit; K1 melamar lalu diterima.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Tidak ada form keputusan. Tampil 'Status lamaran ini Diterima dan tidak dapat diubah lagi lewat halaman ini.'
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-20/TC_PBI20_009_Test.php
 */
class TC_PBI20_009_Test extends CerinDuskTestCase
{
    public function test_tc_pbi20_009(): void
    {
        $this->browse(function (Browser $browser) {
            $umkm = $this->siapkanUmkmTerverifikasi($browser, 'UMKM A');
            $proyek = $this->siapkanProyekTerbit($browser, $umkm, 'Proyek', 2);
            $k1 = $this->daftarKandidat($browser, 'K1');
            $this->lamar($browser, $k1, $proyek);
            $this->putuskan($browser, $umkm, $proyek, $k1, 'accept');

            // ===== Skenario utama =====
            $this->masuk($browser, $umkm);
            $this->bukaSeleksi($browser);
            $this->pilihProyek($browser, $proyek['id']);
            $lamaran = $this->lamaranIdDiTabel($browser, $k1['nama']);
            $this->lihatPelamar($browser, $lamaran);

            $browser->assertMissing('@simpan-keputusan')
                ->assertMissing('@opsi-reject');
            $this->assertSame('Status lamaran ini Diterima dan tidak dapat diubah lagi lewat halaman ini.', $this->teksRapi($browser, '[dusk="status-final"]'));
            $this->assertSame(0, (int) $browser->script("return document.querySelectorAll('form[action*=\"/keputusan\"]').length;")[0]);
            $this->bukti($browser, 'TC_PBI20_009');
        });
    }
}
