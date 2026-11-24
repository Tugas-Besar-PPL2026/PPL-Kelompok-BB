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
 * TC-PBI18-005 (sheet PBI-18, Test_Case_ProyekKita_PBI16_PBI17_PBI18_Revisi.xlsx).
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
#[Group('TC-PBI18-005')]
class TC_PBI18_005_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PersiapanLamaranE2E;
    //use UsesDuskTestDatabase;

    /**
     * TC-PBI18-005 - Riwayat pembatalan tersimpan dan riwayat lama tidak berubah.
     */
    public function test_TC_PBI18_005_riwayat_pembatalan_tersimpan_riwayat_lama_utuh(): void
    {
        $tc = '005';

        $this->browse(function (Browser $browser) use ($tc) {
            // Prasyarat: Admin, UMKM terverifikasi, dan master data dari AdminSeeder (read-only); kandidat dibuat baru lewat UI agar Lamaran Saya hanya berisi data test ini.
            $this->pastikanDataDefaultE2E();
            $kandidat = $this->siapkanKandidatTambahan($browser, 'TC18005');
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
            $this->umkmMemutuskanLewatUi($browser, $kandidat['nama'], [[$proyek[$judul], 'shortlist', null]]);

            // Persiapan 9: login aktor penguji (Kandidat) lewat /login.
            $this->masukSebagaiKandidat($browser, $kandidat);

            // Langkah uji sesuai Excel: klik menu Lamaran Saya di menu samping.
            $this->bukaLamaranSaya($browser);
            $id = $this->idLamaranDariKartu($browser, $judul);
            $sebelum = $this->riwayatDb($id);
            $this->assertCount(2, $sebelum, 'Precondition: 2 entri riwayat.');

            // Batalkan lamaran melalui dialog.
            $this->batalkanLewatDialog($browser, $id);

            // Buka 'Riwayat status' pada lamaran tersebut (lamaran Dibatalkan tidak terbuka otomatis).
            $browser->assertSeeIn("#lamaran-{$id}", 'RIWAYAT STATUS (3)')
                ->click("#lamaran-{$id} details.la-hist summary")
                ->waitFor("#lamaran-{$id} details.la-hist[open] .tl");

            $entri = $this->entriRiwayat($browser, $id);
            $this->assertSame(['Dibatalkan', 'Terpilih sementara', 'Lamaran dikirim'], array_column($entri, 'label'));
            $this->assertContains('Catatan: Dibatalkan oleh kandidat.', $entri[0]['teks']);

            $sesudah = $this->riwayatDb($id);
            $this->assertCount(3, $sesudah);
            $this->assertEquals($sebelum, array_slice($sesudah, 0, 2), 'Dua entri lama tidak boleh berubah.');
            $this->assertSame('Dibatalkan oleh kandidat.', $sesudah[2]->note);
        });
    }
}
