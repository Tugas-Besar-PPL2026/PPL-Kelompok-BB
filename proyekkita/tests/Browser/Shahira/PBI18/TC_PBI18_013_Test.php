<?php

namespace Tests\Browser\PBI18;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\DataDefaultPbi16E2E;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\Browser\Concerns\PersiapanLamaranE2E;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

/**
 * TC-PBI18-013 (sheet PBI-18, Test_Case_ProyekKita_PBI16_PBI17_PBI18_Revisi.xlsx).
 *
 * Alur persiapan E2E (Admin, UMKM terverifikasi, Kandidat default, dan master data E2E disiapkan sekali
 * lewat seeder existing AdminSeeder; skenario lewat Chrome, tanpa loginAs/actingAs/factory/SQL tulis):
 *   1-3. Login UMKM, buat proyek lewat UI, pastikan tersimpan.  4. Logout UMKM.
 *   5-7. Login Admin, setujui di Moderasi Proyek, pastikan Dipublikasikan.  8. Logout Admin.
 *   Precondition lamaran (melamar, keputusan UMKM, pembatalan) juga lewat UI.
 *   9. Login aktor penguji lewat /login, lalu langkah sesuai Excel.
 * Langkah "(Uji HTTP)" dijalankan dari Chrome dengan sesi login aktor (form dimanipulasi seperti lewat DevTools).
 * Database MySQL test persistent hanya dibaca untuk verifikasi tambahan.
 */
#[Group('e2e-lamaran')]
#[Group('pbi18')]
#[Group('TC-PBI18-013')]
class TC_PBI18_013_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PersiapanLamaranE2E;
    //use UsesDuskTestDatabase;

    /**
     * TC-PBI18-013 - Akun kandidat tanpa profil tidak dapat membatalkan.
     */
    public function test_TC_PBI18_013_kandidat_tanpa_profil_tidak_dapat_membatalkan(): void
    {
        $tc = '013';

        $this->browse(function (Browser $browser) use ($tc) {
            // Prasyarat: Admin, UMKM terverifikasi, Kandidat default, dan master data dari AdminSeeder (read-only); kandidat tanpa profil dibuat lewat UI.
            $this->pastikanDataDefaultE2E();
            $kandidat = $this->kandidatDefault();
            $tanpaProfil = $this->siapkanKandidatTambahan($browser, 'TC18013X', 'tanpa-profil');
            $judul = "Dusk PBI18-{$tc} Lamaran {$this->kodeUnik()}";
            $daftarJudul = [$judul];

            // Persiapan 1-3: login UMKM, buat proyek lewat UI, pastikan tersimpan (Proyek Saya: Menunggu Moderasi).
            $this->masukSebagaiUmkm($browser);
            $proyek = [];
            foreach ($daftarJudul as $item) {
                $proyek[$item] = $this->buatProyek($browser, $item);
                $this->umkmMemastikanProyekTersimpan($browser, $proyek[$item], $item);
            }

            // Persiapan 4: logout UMKM.
            $this->keluar($browser);

            // Persiapan 5-7: login Admin, setujui di Moderasi Proyek, pastikan proyek Dipublikasikan.
            $this->masukSebagaiAdmin($browser);
            foreach ($proyek as $item => $id) {
                $this->moderasiProyek($browser, $id, 'approved');
                $this->adminMemastikanProyekDipublikasikan($browser, $id, $item);
            }

            // Persiapan 8: logout Admin.
            $this->keluar($browser);

            // Precondition lamaran lewat UI.
            $this->kandidatMelamarLewatUi($browser, $kandidat, [$judul]);
            $id = (int) $this->lamaranDb($kandidat['email'], $proyek[$judul])->id;

            // Persiapan 9: login aktor penguji (Kandidat tanpa profil) lewat /login.
            $this->masukSebagaiKandidat($browser, $tanpaProfil);

            // Langkah uji sesuai Excel.
            // (Uji HTTP) kirim PATCH ke /kandidat/lamaran/{id}/batal (lamaran milik kandidat lain).
            $this->kirimFormManipulasi($browser, 'PATCH', "/kandidat/lamaran/{$id}/batal");
            $this->assertHalamanGalat($browser, 404, 'Not Found');

            // Tidak ada perubahan data.
            $this->assertSame('pending', $this->statusLamaranDb($id));
            $this->assertCount(1, $this->riwayatDb($id));
        });
    }
}
