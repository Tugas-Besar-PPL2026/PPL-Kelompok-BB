<?php

namespace Tests\Browser\PBI16;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\DuskTestCase;
use Tests\Concerns\UsesDuskTestDatabase;

/**
 * TC-PBI16-021 (sheet PBI-16, Test_Case_ProyekKita_PBI16_PBI17_PBI18_Revisi.xlsx).
 *
 * Alur persiapan E2E (semua lewat Chrome, tanpa loginAs/factory/seeder/SQL):
 *   1. Login UMKM, buat proyek lewat UI.  2. Logout UMKM.
 *   3. Login Admin, setujui proyek di Moderasi Proyek.  4. Logout Admin.
 *   5. Login aktor uji lewat /login, lalu jalankan langkah sesuai Excel.
 * Database MySQL test persistent hanya dibaca untuk verifikasi tambahan.
 */
#[Group('e2e-lamaran')]
#[Group('pbi16')]
#[Group('TC-PBI16-021')]
class TC_PBI16_021_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use UsesDuskTestDatabase;

    /**
     * TC-PBI16-021 - Pengalaman dan portofolio tidak wajib untuk melamar.
     */
    public function test_TC_PBI16_021_pengalaman_dan_portofolio_tidak_wajib(): void
    {
        $this->browse(function (Browser $browser) {
            // Prasyarat akun lewat UI (dipakai ulang bila sudah ada): master data, UMKM terverifikasi, akun kandidat.
            $this->pastikanUmkmTerverifikasi($browser);
            $kandidat = $this->siapkanKandidat($browser, 'TC16021');
            $judul = "Dusk PBI16-021 {$this->kodeUnik()}";

            // Persiapan 1: login UMKM dan buat proyek lewat UI (Buat Proyek -> Tinjau & Ajukan -> ajukan ke moderasi).
            $this->masukSebagaiUmkm($browser);
            $proyekId = $this->buatProyek($browser, $judul);

            // Persiapan 2: logout UMKM.
            $this->keluar($browser);

            // Persiapan 3: login Admin, setujui di Moderasi Proyek, pastikan proyek Dipublikasikan.
            $this->masukSebagaiAdmin($browser);
            $this->moderasiProyek($browser, $proyekId, 'approved');
            $this->adminMemastikanProyekDipublikasikan($browser, $proyekId, $judul);

            // Persiapan 4: logout Admin.
            $this->keluar($browser);

            // Persiapan 5: login Kandidat lewat halaman /login.
            $this->masukSebagaiKandidat($browser, $kandidat);

            // Langkah uji sesuai Excel.
            $this->bukaDetailDariEksplor($browser, $judul)
                ->assertSeeIn('@profil-dilampirkan', '0 pengalaman kerja')
                ->assertSeeIn('@profil-dilampirkan', '0 portofolio')
                ->assertEnabled('@pesan-lamaran')
                ->assertEnabled('@kirim-lamaran');

            $opsional = $browser->driver->executeScript(
                "return [...document.querySelectorAll('[dusk=\"profil-dilampirkan\"] .it')]"
                .".filter(it => /Pengalaman kerja|Portofolio/.test(it.textContent))"
                .".every(it => it.querySelector('.bd').textContent.trim() === 'Opsional');"
            );
            $this->assertTrue($opsional, 'Pengalaman kerja dan Portofolio harus berlabel Opsional.');

            $browser->type('@pesan-lamaran', self::PESAN_VALID)
                ->press('@kirim-lamaran')
                ->waitForText(self::MSG_LAMARAN_TERKIRIM);

            $this->assertNotNull($this->lamaranDb($kandidat['email'], $proyekId));
        });
    }
}
