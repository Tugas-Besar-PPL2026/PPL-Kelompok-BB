<?php

namespace Tests\Browser\PBI16;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\DuskTestCase;
use Tests\Concerns\UsesDuskTestDatabase;

/**
 * TC-PBI16-013 (sheet PBI-16, Test_Case_ProyekKita_PBI16_PBI17_PBI18_Revisi.xlsx).
 *
 * Alur persiapan E2E (semua lewat Chrome, tanpa loginAs/factory/seeder/SQL):
 *   1. Login UMKM, buat proyek lewat UI.  2. Logout UMKM.
 *   3. Login Admin, setujui proyek di Moderasi Proyek.  4. Logout Admin.
 *   5. Login aktor uji lewat /login, lalu jalankan langkah sesuai Excel.
 * Database MySQL test persistent hanya dibaca untuk verifikasi tambahan.
 */
#[Group('e2e-lamaran')]
#[Group('pbi16')]
#[Group('TC-PBI16-013')]
class TC_PBI16_013_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use UsesDuskTestDatabase;

    /**
     * TC-PBI16-013 - Proyek belum dipublikasikan (Draf, Menunggu Tinjauan, Perlu Revisi, Ditolak) tidak dapat dilamar.
     *
     * Pengecualian alur persiapan: proyek SENGAJA tidak disetujui. Keempat status dibuat lewat UI:
     * Draf (Simpan Draf), Menunggu Tinjauan (diajukan, tidak diputuskan Admin),
     * Perlu Revisi dan Ditolak (keputusan Admin di Moderasi Proyek).
     */
    public function test_TC_PBI16_013_proyek_belum_dipublikasikan_tidak_dapat_dilamar(): void
    {
        $this->browse(function (Browser $browser) {
            // Prasyarat akun lewat UI (dipakai ulang bila sudah ada): master data, UMKM terverifikasi, akun kandidat.
            $this->pastikanUmkmTerverifikasi($browser);
            $kandidat = $this->siapkanKandidat($browser, 'TC16013');
            $kode = $this->kodeUnik();

            // Persiapan 1: login UMKM dan buat empat proyek lewat UI.
            $this->masukSebagaiUmkm($browser);
            $proyek = [
                'Draf' => $this->buatProyek($browser, "Dusk PBI16-013 Draf {$kode}", 'draf'),
                'Menunggu Tinjauan' => $this->buatProyek($browser, "Dusk PBI16-013 Tinjau {$kode}"),
                'Perlu Revisi' => $this->buatProyek($browser, "Dusk PBI16-013 Revisi {$kode}"),
                'Ditolak' => $this->buatProyek($browser, "Dusk PBI16-013 Tolak {$kode}"),
            ];

            // Persiapan 2: logout UMKM.
            $this->keluar($browser);

            // Persiapan 3: login Admin, minta perbaikan dan tolak (Draf & Menunggu Tinjauan tidak diputuskan).
            $this->masukSebagaiAdmin($browser);
            $this->moderasiProyek($browser, $proyek['Perlu Revisi'], 'revision_requested', 'Lengkapi deskripsi pekerjaan.');
            $this->moderasiProyek($browser, $proyek['Ditolak'], 'rejected', 'Proyek tidak sesuai ketentuan.');

            // Persiapan 4: logout Admin.
            $this->keluar($browser);

            // Persiapan 5: login Kandidat lewat halaman /login.
            $this->masukSebagaiKandidat($browser, $kandidat);

            // Langkah uji sesuai Excel.
            foreach ($proyek as $status => $id) {
                // Buka detail lewat URL langsung.
                $browser->visit("/kandidat/proyek/{$id}");

                // (Uji HTTP) POST lamaran dengan pesan valid dari browser.
                $browser->visit('/kandidat/lamaran');
                $this->kirimFormManipulasi($browser, 'POST', "/kandidat/proyek/{$id}/lamaran", [['message', self::PESAN_VALID]]);
                $this->assertHalamanGalat($browser, 404, 'Not Found');

                $this->assertSame(0, $this->jumlahLamaranProyek($id), "Proyek {$status} tidak boleh menerima lamaran.");
            }
        });
    }
}
