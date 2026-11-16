<?php

namespace Tests\Browser\Cerin\PBI19;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\CerinDuskTestCase;

/**
 * TC-PBI19-012 — UMKM pemilik proyek membuka dokumen portofolio; UMKM lain ditolak
 *
 * Jenis Test     : Negatif
 * Data disiapkan : 1 proyek terbit; K1 mengunggah cv.pdf sebagai portofolio lalu melamar; UMKM B didaftarkan.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : UMKM pemilik dapat membuka dokumen; UMKM lain mendapat 403.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-19/TC_PBI19_012_Test.php
 */
class TC_PBI19_012_Test extends CerinDuskTestCase
{
    public function test_tc_pbi19_012(): void
    {
        $this->browse(function (Browser $browser) {
            $umkm = $this->siapkanUmkmTerverifikasi($browser, 'UMKM A');
            $proyek = $this->siapkanProyekTerbit($browser, $umkm, 'Proyek', 2);
            $k1 = $this->daftarKandidat($browser, 'K1', ['portofolio' => [['judul' => 'CV Kandidat', 'berkas' => true]]]);
            $this->lamar($browser, $k1, $proyek);
            $umkmB = $this->daftarUmkm($browser, 'UMKM B');

            // ===== Skenario utama =====
            $this->masuk($browser, $umkm);
            $this->bukaSeleksi($browser);
            $this->pilihProyek($browser, $proyek['id']);
            $this->lihatPelamar($browser, $this->lamaranIdDiTabel($browser, $k1['nama']));

            $kartu = $this->kartuPortofolio($browser, 'CV Kandidat');
            $this->assertTrue($kartu['ada']);
            $url = collect($kartu['tautan'])->first(fn ($h) => str_contains((string) $h, '/portofolio/'));
            $this->assertNotNull($url, 'Tautan dokumen cv.pdf tidak tampil.');

            // Klik dokumen portofolio (terbuka di tab baru)
            $awal = $browser->driver->getWindowHandle();
            $browser->click('.pfg a.lk[href*="/portofolio/"]');
            $browser->waitUsing(20, 200, fn () => count($browser->driver->getWindowHandles()) > 1);
            $baru = array_values(array_diff($browser->driver->getWindowHandles(), [$awal]))[0];
            $browser->driver->switchTo()->window($baru);
            $browser->waitUsing(20, 200, fn () => str_contains($browser->driver->getCurrentURL(), '/portofolio/'));
            $this->assertNotContains((string) $browser->driver->getTitle(), ['Forbidden', 'Not Found']);
            $this->bukti($browser, 'TC_PBI19_012_pemilik');
            $browser->driver->close();
            $browser->driver->switchTo()->window($awal);

            // Login dengan akun UMKM lain lalu buka URL dokumen yang sama
            $this->keluar($browser);
            $this->masuk($browser, $umkmB);
            $browser->visit($url)->assertSee('403');
            $this->bukti($browser, 'TC_PBI19_012_umkm_lain');
        });
    }
}
