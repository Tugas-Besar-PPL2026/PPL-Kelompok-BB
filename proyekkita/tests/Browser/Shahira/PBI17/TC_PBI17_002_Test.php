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
 * TC-PBI17-002 (sheet PBI-17, Test_Case_ProyekKita_PBI16_PBI17_PBI18_Revisi.xlsx).
 *
 * Alur persiapan E2E (semua lewat Chrome, tanpa loginAs/actingAs/factory/seeder/SQL tulis):
 *   1-3. Login UMKM, buat proyek lewat UI (5 proyek berstatus + 1 proyek tambahan), pastikan tersimpan.
 *   4. Logout UMKM.
 *   5-7. Login Admin, setujui di Moderasi Proyek, pastikan Dipublikasikan.  8. Logout Admin.
 *   Precondition lamaran (melamar, keputusan UMKM, pembatalan) juga lewat UI. Proyek tambahan TIDAK dilamar.
 *   9. Login aktor penguji lewat /login, lalu langkah sesuai Excel:
 *      - periksa label kelima status, simpan jumlah tab "Menunggu Keputusan" dan "Semua" (dibaca dari UI);
 *      - ajukan lamaran baru ke proyek tambahan lewat Eksplor Proyek;
 *      - buka Lamaran Saya lagi, pastikan kedua jumlah bertambah tepat 1 dari jumlah awal.
 * Database MySQL test persistent hanya dibaca untuk verifikasi tambahan.
 */
#[Group('e2e-lamaran')]
#[Group('pbi17')]
#[Group('TC-PBI17-002')]
class TC_PBI17_002_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use PersiapanLamaranE2E;

    /**
     * TC-PBI17-002 - Kelima status tampil dengan label yang benar, dan jumlah tab "Menunggu Keputusan"
     * serta "Semua" bertambah tepat 1 setelah kandidat mengajukan lamaran baru.
     */
    public function test_TC_PBI17_002_kelima_status_tampil_dengan_label_benar(): void
    {
        $tc = '002';

        $this->browse(function (Browser $browser) use ($tc) {
            // Prasyarat akun lewat UI (dipakai ulang bila sudah ada): master data, UMKM terverifikasi, akun kandidat.
            $this->pastikanUmkmTerverifikasi($browser);
            $kandidat = $this->siapkanKandidat($browser, 'TC17002');
            $kode = $this->kodeUnik();
            $menunggu = "Dusk PBI17-{$tc} Menunggu {$kode}";
            $terpilih = "Dusk PBI17-{$tc} Terpilih {$kode}";
            $diterima = "Dusk PBI17-{$tc} Diterima {$kode}";
            $ditolak = "Dusk PBI17-{$tc} Ditolak {$kode}";
            $batal = "Dusk PBI17-{$tc} Batal {$kode}";
            $tambahan = "Dusk PBI17-{$tc} Tambahan {$kode}";
            $daftarJudul = [$menunggu, $terpilih, $diterima, $ditolak, $batal];

            // Persiapan 1-3: login UMKM, buat proyek lewat UI, pastikan tersimpan (Proyek Saya: Menunggu Moderasi).
            // Proyek tambahan dibuat dengan alur yang sama, tetapi tidak dilamar pada tahap persiapan.
            $this->masukSebagaiUmkm($browser);
            $proyek = [];
            foreach ([...$daftarJudul, $tambahan] as $item) {
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

            // Precondition lamaran lewat UI (hanya kelima proyek berstatus; proyek tambahan tidak dilamar).
            $this->kandidatMelamarLewatUi($browser, $kandidat, $daftarJudul);
            $this->umkmMemutuskanLewatUi($browser, $kandidat['nama'], [
                [$proyek[$terpilih], 'shortlist', null],
                [$proyek[$diterima], 'accept', null],
                [$proyek[$ditolak], 'reject', 'Belum sesuai kebutuhan proyek.'],
            ]);
            $this->kandidatMembatalkanLewatUi($browser, $kandidat, [$batal]);

            // Verifikasi tambahan (baca saja): proyek tambahan benar-benar belum dilamar kandidat ini.
            $this->assertNull(
                $this->lamaranDb($kandidat['email'], $proyek[$tambahan]),
                'Proyek tambahan tidak boleh sudah dilamar sebelum langkah pengajuan lamaran baru.'
            );

            // Persiapan 9: login aktor penguji (Kandidat) lewat /login.
            $this->masukSebagaiKandidat($browser, $kandidat);

            // Langkah uji sesuai Excel: klik menu Lamaran Saya di menu samping.
            $this->bukaLamaranSaya($browser);
            // Periksa label badge setiap lamaran.
            $harapan = [
                $menunggu => 'Menunggu Keputusan',
                $terpilih => 'Terpilih Sementara',
                $diterima => 'Diterima',
                $ditolak => 'Ditolak',
                $batal => 'Dibatalkan',
            ];

            foreach ($harapan as $item => $label) {
                $browser->assertSeeIn('#lamaran-'.$this->idLamaranDariKartu($browser, $item).' .la-meta .bd', $label);
            }

            // Proyek tambahan belum muncul sebagai lamaran.
            $browser->assertDontSee($tambahan);

            // Jumlah awal dari angka pada tab filter (nav.la-tabs). Kandidat dibuat baru oleh siapkanKandidat()
            // dan setup menghasilkan tepat 5 lamaran dengan status berbeda, jadi jumlah awal divalidasi dulu.
            $tabAwal = $this->isiTabLamaran($browser);
            $this->assertArrayHasKey('Menunggu Keputusan', $tabAwal, 'Tab Menunggu Keputusan harus tampil.');
            $this->assertArrayHasKey('Semua', $tabAwal, 'Tab Semua harus tampil.');
            $this->assertSame(1, $tabAwal['Menunggu Keputusan']['jumlah'], 'Jumlah awal tab Menunggu Keputusan harus 1.');
            $this->assertSame(5, $tabAwal['Semua']['jumlah'], 'Jumlah awal tab Semua harus 5.');

            // Simpan sebagai baseline untuk perbandingan setelah pengajuan lamaran baru.
            $menungguAwal = $tabAwal['Menunggu Keputusan']['jumlah'];
            $semuaAwal = $tabAwal['Semua']['jumlah'];

            // Langkah uji: kandidat yang sama mengajukan lamaran ke proyek tambahan lewat Eksplor Proyek.
            $this->ajukanLamaran($browser, $tambahan)
                ->assertSeeIn('@status-lamaran', 'Lamaran Anda sudah terkirim')
                ->assertSeeIn('@status-lamaran', 'Menunggu Keputusan');

            // Verifikasi tambahan (baca saja): lamaran baru tersimpan untuk kandidat ini dengan status pending.
            $this->assertSame(
                'pending',
                $this->lamaranDb($kandidat['email'], $proyek[$tambahan])?->status,
                'Lamaran baru harus tersimpan dengan status pending.'
            );

            // Langkah uji: kembali ke Lamaran Saya lewat menu samping.
            $this->bukaLamaranSaya($browser);
            $browser->assertSeeIn(
                '#lamaran-'.$this->idLamaranDariKartu($browser, $tambahan).' .la-meta .bd',
                'Menunggu Keputusan'
            );

            // Bandingkan jumlah setelah pengajuan dengan jumlah awal yang disimpan.
            $tabAkhir = $this->isiTabLamaran($browser);
            $this->assertSame(
                $menungguAwal + 1,
                $tabAkhir['Menunggu Keputusan']['jumlah'],
                "Jumlah tab Menunggu Keputusan harus bertambah tepat 1 (awal {$menungguAwal})."
            );
            $this->assertSame(
                $semuaAwal + 1,
                $tabAkhir['Semua']['jumlah'],
                "Jumlah tab Semua harus bertambah tepat 1 (awal {$semuaAwal})."
            );
        });
    }
}