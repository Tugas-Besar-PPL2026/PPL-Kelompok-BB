<?php

namespace Tests\Browser\PBI17;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\Browser\Concerns\PersiapanLamaranE2E;
use Tests\DuskTestCase;
use Tests\Concerns\UsesDuskTestDatabase;

/**
 * TC-PBI17-007 (sheet PBI-17, Test_Case_ProyekKita_PBI16_PBI17_PBI18_Revisi.xlsx).
 *
 * Alur persiapan E2E (semua lewat Chrome, tanpa loginAs/actingAs/factory/seeder/SQL tulis):
 *   1-3. Login UMKM, buat proyek lewat UI, pastikan tersimpan.  4. Logout UMKM.
 *   5-7. Login Admin, setujui di Moderasi Proyek, pastikan Dipublikasikan.  8. Logout Admin.
 *   Precondition lamaran (melamar, keputusan UMKM, pembatalan) juga lewat UI.
 *   9. Login aktor penguji lewat /login, lalu langkah sesuai Excel.
 * Database MySQL test persistent hanya dibaca untuk verifikasi tambahan.
 */
#[Group('e2e-lamaran')]
#[Group('pbi17')]
#[Group('TC-PBI17-007')]
class TC_PBI17_007_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
      use MenjagaDatabaseE2E;
//use UsesDuskTestDatabase;
    use PersiapanLamaranE2E;
     

    /**
     * TC-PBI17-007 - Riwayat status diurutkan terbaru dulu dengan tanggal yang benar.
     *
     * Urutan dan tanggal yang tampil dibandingkan dengan data riwayat di database (changed_at desc, id desc),
     * sehingga aturan pemecah seri ikut terverifikasi bila dua entri tercatat pada detik yang sama.
     * Kendala UI: dua entri dengan waktu persis sama tidak dapat dipaksa lewat UI (setiap keputusan UMKM butuh
     * beberapa detik), dan entri tanpa tanggal tidak dapat dibuat karena aplikasi selalu mengisi changed_at.
     * Kedua kondisi itu tetap diuji Feature test LamaranSayaTest (test_riwayat_diurutkan_terbaru_dulu_dan_id_memecah_seri,
     * test_tanggal_riwayat_kosong_tidak_dikarang).
     */
    public function test_TC_PBI17_007_riwayat_terbaru_dulu_dengan_format_tanggal(): void
    {
        $tc = '007';

        $this->browse(function (Browser $browser) use ($tc) {
            // Prasyarat akun lewat UI (dipakai ulang bila sudah ada): master data, UMKM terverifikasi, akun kandidat.
            $this->pastikanUmkmTerverifikasi($browser);
            $kandidat = $this->siapkanKandidat($browser, 'TC17007');
            $kode = $this->kodeUnik();
            $judul = "Dusk PBI17-{$tc} Riwayat {$kode}";
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
            $this->umkmMemutuskanLewatUi($browser, $kandidat['nama'], [
                [$proyek[$judul], 'shortlist', null],
                [$proyek[$judul], 'accept', null],
            ]);

            // Persiapan 9: login aktor penguji (Kandidat) lewat /login.
            $this->masukSebagaiKandidat($browser, $kandidat);

            // Langkah uji sesuai Excel: klik menu Lamaran Saya di menu samping.
            $this->bukaLamaranSaya($browser);
            $id = $this->idLamaranDariKartu($browser, $judul);

            // Buka 'Riwayat status' pada lamaran (lamaran Diterima tidak terbuka otomatis).
            $browser->click("#lamaran-{$id} details.la-hist summary")
                ->waitFor("#lamaran-{$id} details.la-hist[open] .tl");

            // Periksa urutan dan tanggal setiap entri.
            $entri = $this->entriRiwayat($browser, $id);
            $this->assertSame(['Diterima', 'Terpilih sementara', 'Lamaran dikirim'], array_column($entri, 'label'));
            $this->assertSame($this->riwayatDiharapkanDariDb($id), array_map(
                fn ($item) => ['label' => $item['label'], 'tanggal' => $item['tanggal']],
                $entri
            ));

            foreach ($entri as $item) {
                $this->assertMatchesRegularExpression('/^\d{1,2} \S+ \d{4}, \d{2}\.\d{2}$/u', (string) $item['tanggal']);
            }
        });
    }
}
