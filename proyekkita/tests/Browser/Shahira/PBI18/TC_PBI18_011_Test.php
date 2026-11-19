<?php

namespace Tests\Browser\PBI18;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\Browser\Concerns\PersiapanLamaranE2E;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

/**
 * TC-PBI18-011 (sheet PBI-18, Test_Case_ProyekKita_PBI16_PBI17_PBI18_Revisi.xlsx).
 *
 * Alur persiapan E2E (semua lewat Chrome, tanpa loginAs/actingAs/factory/seeder/SQL tulis):
 *   1-3. Login UMKM, buat proyek lewat UI, pastikan tersimpan.  4. Logout UMKM.
 *   5-7. Login Admin, setujui di Moderasi Proyek, pastikan Dipublikasikan.  8. Logout Admin.
 *   Precondition lamaran (melamar, keputusan UMKM, pembatalan) juga lewat UI.
 *   9. Login aktor penguji lewat /login, lalu langkah sesuai Excel.
 * Langkah "(Uji HTTP)" dijalankan dari Chrome dengan sesi login aktor (form dimanipulasi seperti lewat DevTools).
 * Database MySQL test persistent hanya dibaca untuk verifikasi tambahan.
 */
#[Group('e2e-lamaran')]
#[Group('pbi18')]
#[Group('TC-PBI18-011')]
class TC_PBI18_011_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
      use MenjagaDatabaseE2E;
//use UsesDuskTestDatabase;
    use PersiapanLamaranE2E;
     

    /**
     * TC-PBI18-011 - Lamaran tidak ditemukan atau ID tidak valid menghasilkan 404.
     */
    public function test_TC_PBI18_011_id_tidak_ditemukan_atau_tidak_valid_404(): void
    {
        $this->browse(function (Browser $browser) {
            // Prasyarat akun lewat UI: akun kandidat. Tidak membutuhkan proyek atau lamaran.
            $kandidat = $this->siapkanKandidat($browser, 'TC18011');
            $jumlahLamaran = DB::table('applications')->count();
            $jumlahRiwayat = DB::table('application_status_histories')->count();

            // Persiapan 9: login aktor penguji (Kandidat) lewat /login.
            $this->masukSebagaiKandidat($browser, $kandidat);

            // Langkah uji sesuai Excel.
            foreach ([(string) $this->idTidakAda('applications', 99999), '0', '-1', 'abc'] as $idUji) {
                // Halaman galat tidak memiliki menu; buka Lamaran Saya lewat URL sebagai halaman asal request.
                $browser->visit('/kandidat/lamaran')->waitFor('nav.la-tabs');

                // (Uji HTTP) kirim PATCH ke /kandidat/lamaran/{id}/batal.
                $this->kirimFormManipulasi($browser, 'PATCH', "/kandidat/lamaran/{$idUji}/batal");
                $this->assertHalamanGalat($browser, 404, 'Not Found');
            }

            // Data tidak berubah.
            $this->assertSame($jumlahLamaran, DB::table('applications')->count());
            $this->assertSame($jumlahRiwayat, DB::table('application_status_histories')->count());
        });
    }
}
