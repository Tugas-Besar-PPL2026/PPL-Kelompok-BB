<?php

namespace Tests\Browser\PBI17;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\DataDefaultPbi16E2E;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\Browser\Concerns\PersiapanLamaranE2E;
use Tests\DuskTestCase;
use Tests\Concerns\UsesDuskTestDatabase;

/**
 * TC-PBI17-015 (sheet PBI-17, Test_Case_ProyekKita_PBI16_PBI17_PBI18_Revisi.xlsx).
 *
 * Alur persiapan E2E (Admin, UMKM terverifikasi, Kandidat default, dan master data E2E disiapkan sekali
 * lewat seeder existing AdminSeeder; skenario lewat Chrome, tanpa loginAs/actingAs/factory/SQL tulis):
 *   1-3. Login UMKM, buat proyek lewat UI, pastikan tersimpan.  4. Logout UMKM.
 *   5-7. Login Admin, setujui di Moderasi Proyek, pastikan Dipublikasikan.  8. Logout Admin.
 *   Precondition lamaran (melamar, keputusan UMKM, pembatalan) juga lewat UI.
 *   9. Login aktor penguji lewat /login, lalu langkah sesuai Excel.
 * Database MySQL test persistent hanya dibaca untuk verifikasi tambahan.
 */
#[Group('e2e-lamaran')]
#[Group('pbi17')]
#[Group('TC-PBI17-015')]
class TC_PBI17_015_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PersiapanLamaranE2E;
    //use UsesDuskTestDatabase;

    /**
     * TC-PBI17-015 - Pagination: 10 per halaman, navigasi berfungsi, filter terbawa, tepat 10 tanpa pager.
     */
    public function test_TC_PBI17_015_pagination(): void
    {
        $tc = '015';

        $this->browse(function (Browser $browser) use ($tc) {
            // Prasyarat: Admin, UMKM terverifikasi, dan master data dari AdminSeeder (read-only); kandidat dibuat baru lewat UI agar Lamaran Saya hanya berisi data test ini.
            $this->pastikanDataDefaultE2E();
            $kandidatA = $this->siapkanKandidatTambahan($browser, 'TC17015A');
            $kandidatB = $this->siapkanKandidatTambahan($browser, 'TC17015B');
            $kode = $this->kodeUnik();
            $daftarJudul = array_map(fn ($n) => sprintf('Dusk PBI17-%s %02d %s', $tc, $n, $kode), range(1, 11));

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
            // Skenario A: 11 lamaran Menunggu Keputusan (dilamar berurutan 01..11). Skenario B: tepat 10 lamaran.
            $this->kandidatMelamarLewatUi($browser, $kandidatA, $daftarJudul);
            $this->kandidatMelamarLewatUi($browser, $kandidatB, array_slice($daftarJudul, 0, 10));

            // Persiapan 9: login aktor penguji (Kandidat skenario A) lewat /login.
            $this->masukSebagaiKandidat($browser, $kandidatA);

            // Langkah uji sesuai Excel: klik menu Lamaran Saya di menu samping.
            $this->bukaLamaranSaya($browser);
            // Amati teks 'Halaman 1 dari 2'.
            $browser->assertSeeIn('.la-pager', 'Halaman 1 dari 2');
            $this->assertSame(array_reverse(array_slice($daftarJudul, 1)), $this->urutanJudulKartu($browser), 'Halaman 1 memuat 10 lamaran terbaru.');

            // Klik 'Berikutnya', lalu 'Sebelumnya'.
            $this->klikTautan($browser, 'Berikutnya')->assertSeeIn('.la-pager', 'Halaman 2 dari 2');
            $this->assertSame([$daftarJudul[0]], $this->urutanJudulKartu($browser), 'Halaman 2 memuat 1 lamaran.');
            $this->klikTautan($browser, 'Sebelumnya')->assertSeeIn('.la-pager', 'Halaman 1 dari 2');

            // Klik tab 'Menunggu Keputusan', lalu 'Berikutnya' dan periksa URL.
            $this->klikTautan($browser, 'Menunggu Keputusan')->assertQueryStringHas('status', 'pending');
            $this->klikTautan($browser, 'Berikutnya')
                ->assertSeeIn('.la-pager', 'Halaman 2 dari 2')
                ->assertQueryStringHas('status', 'pending')
                ->assertQueryStringHas('page', '2');

            // Keluar, login dengan kandidat skenario B, buka Lamaran Saya.
            $this->keluar($browser);
            $this->masukSebagaiKandidat($browser, $kandidatB);
            $this->bukaLamaranSaya($browser)->assertMissing('.la-pager');
            $this->assertEqualsCanonicalizing(array_slice($daftarJudul, 0, 10), $this->urutanJudulKartu($browser));
        });
    }
}
