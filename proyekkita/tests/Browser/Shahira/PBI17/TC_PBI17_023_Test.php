<?php

namespace Tests\Browser\PBI17;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\Browser\Concerns\PersiapanLamaranE2E;
use Tests\DuskTestCase;

/**
 * TC-PBI17-023 (sheet PBI-17, Test_Case_ProyekKita_PBI16_PBI17_PBI18_Revisi.xlsx).
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
#[Group('TC-PBI17-023')]
class TC_PBI17_023_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use PersiapanLamaranE2E;

    /**
     * TC-PBI17-023 - Jumlah query tidak bertambah seiring jumlah lamaran (tanpa N+1).
     *
     * Jumlah query diukur saat halaman Lamaran Saya dimuat ulang di Chrome, dengan membaca
     * performance_schema.events_statements_summary_by_digest MySQL untuk schema database test (read-only).
     * Kondisi 1: kandidat A dengan 1 lamaran; kondisi 2: kandidat B dengan 6 lamaran; setiap lamaran memiliki
     * 2 entri riwayat (Menunggu Keputusan -> Terpilih Sementara, keputusan UMKM lewat UI).
     * Dilewati hanya bila performance_schema MySQL tidak aktif atau tidak dapat dibaca oleh user database test.
     */
    public function test_TC_PBI17_023_tanpa_n_plus_1_query(): void
    {
        $tc = '023';

        $this->browse(function (Browser $browser, Browser $sesiKedua) use ($tc) {
            // Prasyarat teknis: performance_schema MySQL dapat dibaca.
            if ($this->jumlahStatementSchemaTest() === null) {
                $this->markTestSkipped('TC-PBI17-023: performance_schema MySQL tidak aktif atau tidak dapat dibaca, sehingga jumlah query halaman tidak dapat diukur dari luar aplikasi. Aktifkan performance_schema atau beri hak SELECT pada user database test.');
            }

            // Prasyarat akun lewat UI (dipakai ulang bila sudah ada): master data, UMKM terverifikasi, akun kandidat.
            $this->pastikanUmkmTerverifikasi($browser);
            $kandidatA = $this->siapkanKandidat($browser, 'TC17023A');
            $kandidatB = $this->siapkanKandidat($browser, 'TC17023B');
            $kode = $this->kodeUnik();
            $daftarJudul = array_map(fn ($n) => sprintf('Dusk PBI17-%s %02d %s', $tc, $n, $kode), range(1, 7));
            $judulA = $daftarJudul[0];
            $judulB = array_slice($daftarJudul, 1);

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
            $this->kandidatMelamarLewatUi($browser, $kandidatA, [$judulA]);
            $this->kandidatMelamarLewatUi($browser, $kandidatB, $judulB);
            $this->umkmMemutuskanLewatUi($browser, $kandidatA['nama'], [[$proyek[$judulA], 'shortlist', null]]);
            $this->umkmMemutuskanLewatUi($browser, $kandidatB['nama'], array_map(fn ($item) => [$proyek[$item], 'shortlist', null], $judulB));

            // Langkah 1: hitung jumlah query saat membuka Lamaran Saya dengan 1 lamaran (sesi Chrome kandidat A).
            $this->masukSebagaiKandidat($browser, $kandidatA);
            $this->bukaLamaranSaya($browser);
            $this->assertCount(1, $this->urutanJudulKartu($browser));
            $querySatu = $this->jumlahQueryMuatLamaranSaya($browser);

            // Langkah 2: hitung jumlah query dengan 6 lamaran (sesi Chrome kandidat B).
            $this->masukSebagaiKandidat($sesiKedua, $kandidatB);
            $this->bukaLamaranSaya($sesiKedua);
            $this->assertCount(6, $this->urutanJudulKartu($sesiKedua));
            foreach ($judulB as $item) {
                $this->assertCount(2, $this->riwayatDb($this->idLamaranDariKartu($sesiKedua, $item)), 'Setiap lamaran memiliki 2 entri riwayat.');
            }
            $queryEnam = $this->jumlahQueryMuatLamaranSaya($sesiKedua);

            // Langkah 3: bandingkan jumlah query.
            $this->assertNotNull($querySatu);
            $this->assertNotNull($queryEnam);
            $this->assertSame($querySatu, $queryEnam, "Jumlah query 1 lamaran ({$querySatu}) harus sama dengan 6 lamaran ({$queryEnam}).");
        });
    }
}
