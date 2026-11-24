<?php

namespace Tests\Browser\PBI18;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\DataDefaultPbi16E2E;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\Browser\Concerns\PersiapanLamaranE2E;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;
use Throwable;

/**
 * TC-PBI18-015 (sheet PBI-18, Test_Case_ProyekKita_PBI16_PBI17_PBI18_Revisi.xlsx).
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
#[Group('TC-PBI18-015')]
class TC_PBI18_015_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PersiapanLamaranE2E;
    //use UsesDuskTestDatabase;

    /**
     * TC-PBI18-015 - Pembatalan atomik: status dan riwayat dikembalikan bila penulisan riwayat gagal.
     *
     * Pembatalan dijalankan lewat UI (dialog "Ya, Batalkan Lamaran"). Kegagalan penulisan riwayat dipicu
     * dengan trigger MySQL sementara yang hanya menolak INSERT riwayat 'cancelled' untuk lamaran test ini,
     * lalu dihapus kembali di blok finally. Karena trigger adalah perubahan pada database test, test ini
     * dilewati kecuali DUSK_IZINKAN_SIMULASI_DB=true; dilewati juga bila user database tidak punya hak TRIGGER.
     */
    #[Group('butuh-simulasi-db')]
    public function test_TC_PBI18_015_pembatalan_atomik(): void
    {
        $this->butuhSimulasiDb('TC-PBI18-015', 'membutuhkan trigger MySQL sementara untuk memaksa penulisan riwayat gagal; kegagalan database tidak dapat dipicu dari UI.');

        $tc = '015';

        $this->browse(function (Browser $browser) use ($tc) {
            // Prasyarat: Admin, UMKM terverifikasi, dan master data dari AdminSeeder (read-only); kandidat dibuat baru lewat UI agar Lamaran Saya hanya berisi data test ini.
            $this->pastikanDataDefaultE2E();
            $kandidat = $this->siapkanKandidatTambahan($browser, 'TC18015');
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

            // Persiapan 9: login aktor penguji (Kandidat) lewat /login.
            $this->masukSebagaiKandidat($browser, $kandidat);

            // Langkah uji sesuai Excel: klik menu Lamaran Saya di menu samping.
            $this->bukaLamaranSaya($browser);
            $id = $this->idLamaranDariKartu($browser, $judul);
            $trigger = "dusk_tc_pbi18_015_{$id}";

            // Paksa penulisan riwayat gagal saat pembatalan (hanya untuk lamaran ini).
            try {
                DB::unprepared("DROP TRIGGER IF EXISTS {$trigger}");
                DB::unprepared(
                    "CREATE TRIGGER {$trigger} BEFORE INSERT ON application_status_histories FOR EACH ROW "
                    ."BEGIN IF NEW.application_id = {$id} AND NEW.status = 'cancelled' THEN "
                    ."SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Simulasi kegagalan riwayat TC-PBI18-015'; END IF; END"
                );
            } catch (Throwable $e) {
                $this->markTestSkipped('TC-PBI18-015: trigger MySQL tidak dapat dibuat oleh user database test ('.$e->getMessage().'). Beri hak TRIGGER pada database test.');
            }

            try {
                // Jalankan pembatalan lewat dialog.
                $browser->click("@batalkan-lamaran-{$id}")->waitFor("@dialog-batal-{$id}");
                $this->tandaiHalamanLama($browser);
                $browser->press("@konfirmasi-batal-{$id}");
                $this->tungguHalamanBaru($browser)->assertDontSee(self::MSG_BATAL_SUKSES);
            } finally {
                DB::unprepared("DROP TRIGGER IF EXISTS {$trigger}");
            }

            // Periksa status dan riwayat: transaksi dibatalkan.
            $this->assertSame('pending', $this->statusLamaranDb($id), 'Status harus kembali Menunggu Keputusan.');
            $this->assertSame(['pending'], array_column($this->riwayatDb($id), 'status'), 'Tidak boleh ada riwayat pembatalan.');

            $browser->visit('/kandidat/lamaran')->waitFor("#lamaran-{$id}")
                ->assertSeeIn("#lamaran-{$id} .la-meta .bd", 'Menunggu Keputusan')
                ->assertPresent("@batalkan-lamaran-{$id}");
        });
    }
}
