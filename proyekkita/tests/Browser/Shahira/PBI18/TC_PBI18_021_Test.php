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
 * TC-PBI18-021 (sheet PBI-18, Test_Case_ProyekKita_PBI16_PBI17_PBI18_Revisi.xlsx).
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
#[Group('TC-PBI18-021')]
class TC_PBI18_021_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PersiapanLamaranE2E;
    //use UsesDuskTestDatabase;

    /**
     * TC-PBI18-021 - Lamaran dibatalkan tidak lagi dihitung pada sisi UMKM (integrasi PBI-19).
     */
    public function test_TC_PBI18_021_lamaran_dibatalkan_tidak_dihitung_di_umkm(): void
    {
        $tc = '021';

        $this->browse(function (Browser $browser) use ($tc) {
            // Prasyarat: Admin, UMKM terverifikasi, dan master data dari AdminSeeder (read-only); kandidat dibuat baru lewat UI agar Lamaran Saya hanya berisi data test ini.
            $this->pastikanDataDefaultE2E();
            $kandidat = $this->siapkanKandidatTambahan($browser, 'TC18021');
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

            $tautanPelamar = 'a[aria-label="Lihat detail pelamar '.$kandidat['nama'].'"]';

            // Langkah 1-5: login UMKM lewat /login, klik menu Seleksi Kandidat, catat statistik dan jumlah pelamar.
            $this->masukSebagaiUmkm($browser);
            $this->bukaSeleksiProyek($browser, $proyek[$judul])->assertPresent($tautanPelamar);
            $sebelum = $this->statistikSeleksi($browser);
            $kuotaSebelum = $browser->text('@kuota-daftar');
            $this->keluar($browser);

            // Langkah 6: keluar, login sebagai kandidat, batalkan lamaran melalui Lamaran Saya.
            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaLamaranSaya($browser);
            $this->batalkanLewatDialog($browser, $this->idLamaranDariKartu($browser, $judul));
            $this->keluar($browser);

            // Langkah 7: keluar, login kembali sebagai UMKM, buka Seleksi Kandidat dan bandingkan.
            $this->masukSebagaiUmkm($browser);
            $this->bukaSeleksiProyek($browser, $proyek[$judul])->assertMissing($tautanPelamar);
            $sesudah = $this->statistikSeleksi($browser);

            $this->assertSame($sebelum['Total pelamar'] - 1, $sesudah['Total pelamar']);
            $this->assertSame($sebelum['Menunggu keputusan'] - 1, $sesudah['Menunggu keputusan']);
            $this->assertSame($sebelum['Diterima'], $sesudah['Diterima'], 'Jumlah Diterima tidak berubah.');
            $this->assertSame($kuotaSebelum, $browser->text('@kuota-daftar'), 'Sisa kuota tidak berubah.');
        });
    }

    /** Angka pada kartu statistik halaman Seleksi Kandidat: label => nilai pertama (mis. "0 / 1" -> 0). */
    private function statistikSeleksi(Browser $browser): array
    {
        $data = $browser->driver->executeScript(
            "return [...document.querySelectorAll('.stats .stat')].map(s => [s.querySelector('.l').textContent.trim(), s.querySelector('.v').textContent.trim()]);"
        );

        $hasil = [];
        foreach ($data as [$label, $nilai]) {
            $hasil[$label] = (int) trim(explode('/', $nilai)[0]);
        }

        return $hasil;
    }
}
