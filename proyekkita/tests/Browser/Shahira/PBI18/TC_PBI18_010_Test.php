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
 * TC-PBI18-010 (sheet PBI-18, Test_Case_ProyekKita_PBI16_PBI17_PBI18_Revisi.xlsx).
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
#[Group('TC-PBI18-010')]
class TC_PBI18_010_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PersiapanLamaranE2E;
    //use UsesDuskTestDatabase;

    /**
     * TC-PBI18-010 - Kandidat tidak dapat membatalkan lamaran milik kandidat lain.
     */
    public function test_TC_PBI18_010_tidak_dapat_membatalkan_lamaran_kandidat_lain(): void
    {
        $tc = '010';

        $this->browse(function (Browser $browser) use ($tc) {
            // Prasyarat: Admin, UMKM terverifikasi, dan master data dari AdminSeeder (read-only); kandidat dibuat baru lewat UI agar Lamaran Saya hanya berisi data test ini.
            $this->pastikanDataDefaultE2E();
            $kandidatA = $this->siapkanKandidatTambahan($browser, 'TC18010A');
            $kandidatB = $this->siapkanKandidatTambahan($browser, 'TC18010B');
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
            $this->kandidatMelamarLewatUi($browser, $kandidatA, [$judul]);
            // Input "ID lamaran milik A" dibaca dari database (read-only).
            $idA = (int) $this->lamaranDb($kandidatA['email'], $proyek[$judul])->id;

            // Persiapan 9: login aktor penguji (Kandidat B) lewat /login.
            $this->masukSebagaiKandidat($browser, $kandidatB);

            // Langkah uji sesuai Excel: klik menu Lamaran Saya, pastikan lamaran milik A tidak tampil.
            $this->bukaLamaranSaya($browser)->assertDontSee($judul);
            $this->assertNotContains($judul, $this->urutanJudulKartu($browser));

            // (Uji HTTP) kirim PATCH ke /kandidat/lamaran/{id lamaran A}/batal dari sesi kandidat B.
            $this->kirimFormManipulasi($browser, 'PATCH', "/kandidat/lamaran/{$idA}/batal");
            $this->assertHalamanGalat($browser, 404, 'Not Found');

            // Status lamaran A tidak berubah.
            $this->assertSame('pending', $this->statusLamaranDb($idA));
            $this->assertCount(1, $this->riwayatDb($idA));
        });
    }
}
