<?php

namespace Tests\Browser\PBI16;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\DuskTestCase;
use Tests\Concerns\UsesDuskTestDatabase;

/**
 * TC-PBI16-016 (sheet PBI-16, Test_Case_ProyekKita_PBI16_PBI17_PBI18_Revisi.xlsx).
 *
 * Alur persiapan E2E (semua lewat Chrome, tanpa loginAs/factory/seeder/SQL):
 *   1. Login UMKM, buat proyek lewat UI.  2. Logout UMKM.
 *   3. Login Admin, setujui proyek di Moderasi Proyek.  4. Logout Admin.
 *   5. Login aktor uji lewat /login, lalu jalankan langkah sesuai Excel.
 * Database MySQL test persistent hanya dibaca untuk verifikasi tambahan.
 */
#[Group('e2e-lamaran')]
#[Group('pbi16')]
#[Group('TC-PBI16-016')]
class TC_PBI16_016_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use UsesDuskTestDatabase;

    /**
     * TC-PBI16-016 - Kandidat berbeda boleh melamar proyek yang sama.
     */
    public function test_TC_PBI16_016_kandidat_berbeda_boleh_melamar_proyek_sama(): void
    {
        $this->browse(function (Browser $browser) {
            // Prasyarat akun lewat UI: master data, UMKM terverifikasi, kandidat A dan kandidat B.
            $this->pastikanUmkmTerverifikasi($browser);
            $kandidatA = $this->siapkanKandidat($browser, 'TC16016A');
            $kandidatB = $this->siapkanKandidat($browser, 'TC16016B');
            $judul = "Dusk PBI16-016 {$this->kodeUnik()}";

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
            $this->masukSebagaiKandidat($browser, $kandidatA);

            // Langkah uji sesuai Excel.
            // Kandidat A melamar.
            $this->bukaDetailDariEksplor($browser, $judul)
                ->type('@pesan-lamaran', self::PESAN_VALID)
                ->press('@kirim-lamaran')
                ->waitForText(self::MSG_LAMARAN_TERKIRIM);

            // Keluar, login sebagai kandidat B, lamar proyek yang sama.
            $this->keluar($browser);
            $this->masukSebagaiKandidat($browser, $kandidatB);
            $this->bukaDetailDariEksplor($browser, $judul)
                ->type('@pesan-lamaran', self::PESAN_VALID)
                ->press('@kirim-lamaran')
                ->waitForText(self::MSG_LAMARAN_TERKIRIM)
                ->assertSeeIn('@status-lamaran', 'Lamaran Anda sudah terkirim');

            $this->assertSame(2, $this->jumlahLamaranProyek($proyekId));
            $this->assertNotNull($this->lamaranDb($kandidatA['email'], $proyekId));
            $this->assertNotNull($this->lamaranDb($kandidatB['email'], $proyekId));
        });
    }
}
