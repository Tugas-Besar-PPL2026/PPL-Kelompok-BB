<?php

namespace Tests\Browser\PBI16;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\DataDefaultPbi16E2E;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\DuskTestCase;
use Tests\Concerns\UsesDuskTestDatabase;

/**
 * TC-PBI16-008 (sheet PBI-16, Test_Case_ProyekKita_PBI16_PBI17_PBI18_Revisi.xlsx).
 *
 * Alur persiapan E2E (Admin, UMKM terverifikasi, Kandidat default, dan master data PBI-16 disiapkan sekali
 * lewat seeder existing AdminSeeder; skenario lewat Chrome, tanpa loginAs/factory/SQL tulis):
 *   1. Login UMKM, buat proyek lewat UI.  2. Logout UMKM.
 *   3. Login Admin, setujui proyek di Moderasi Proyek.  4. Logout Admin.
 *   5. Login aktor uji lewat /login, lalu jalankan langkah sesuai Excel.
 * Database MySQL test persistent hanya dibaca untuk verifikasi tambahan.
 */
#[Group('e2e-lamaran')]
#[Group('pbi16')]
#[Group('TC-PBI16-008')]
class TC_PBI16_008_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    //use UsesDuskTestDatabase;

    /**
     * TC-PBI16-008 - Kolom status/kandidat/proyek tambahan pada request diabaikan (mass assignment).
     *
     * Parameter tambahan disisipkan ke form asli lewat DOM, lalu form dikirim dengan tombol Kirim Lamaran.
     * Kandidat lain dan proyek lain dibuat lewat UI agar ID yang disisipkan benar-benar ada.
     */
    public function test_TC_PBI16_008_kolom_tambahan_pada_request_diabaikan(): void
    {
        $this->browse(function (Browser $browser) {
            // Prasyarat: data default dari AdminSeeder (read-only); kandidat penguji = Kandidat default, kandidat lain dibuat lewat UI.
            $this->pastikanDataDefaultPbi16();
            $kandidatLain = $this->siapkanKandidatTambahan($browser, 'TC16008Lain');
            $kandidat = $this->kandidatDefault();
            $kode = $this->kodeUnik();
            $judulA = "Dusk PBI16-008 A {$kode}";
            $judulB = "Dusk PBI16-008 B {$kode}";

            // Persiapan 1: login UMKM dan buat dua proyek lewat UI.
            $this->masukSebagaiUmkm($browser);
            $idA = $this->buatProyek($browser, $judulA);
            $idB = $this->buatProyek($browser, $judulB);

            // Persiapan 2: logout UMKM.
            $this->keluar($browser);

            // Persiapan 3: login Admin, setujui kedua proyek, pastikan Dipublikasikan.
            $this->masukSebagaiAdmin($browser);
            $this->moderasiProyek($browser, $idA, 'approved');
            $this->moderasiProyek($browser, $idB, 'approved');
            $this->adminMemastikanProyekDipublikasikan($browser, $idA, $judulA);
            $this->adminMemastikanProyekDipublikasikan($browser, $idB, $judulB);

            // Persiapan 4: logout Admin.
            $this->keluar($browser);

            // Persiapan 5: login Kandidat lewat halaman /login.
            $this->masukSebagaiKandidat($browser, $kandidat);

            // Langkah uji sesuai Excel.
            // Proyek A = proyek pada URL, proyek B = "proyek lain" yang disisipkan.
            $this->bukaDetailDariEksplor($browser, $judulA);

            $browser->driver->executeScript(<<<'JS'
                const form = document.getElementById('form-lamaran');
                for (const [nama, nilai] of arguments[0]) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = nama;
                    input.value = nilai;
                    form.appendChild(input);
                }
            JS, [[
                ['status', 'accepted'],
                ['candidate_profile_id', (string) $this->idProfilKandidat($kandidatLain['email'])],
                ['project_id', (string) $idB],
                ['selection_note', 'Disisipkan oleh pelamar'],
            ]]);

            $browser->type('@pesan-lamaran', self::PESAN_VALID)
                ->press('@kirim-lamaran')
                ->waitForText(self::MSG_LAMARAN_TERKIRIM)
                ->assertSeeIn('@status-lamaran', 'Menunggu Keputusan');

            $lamaran = $this->lamaranDb($kandidat['email'], $idA);
            $this->assertNotNull($lamaran, 'Lamaran harus tersimpan untuk kandidat login dan proyek pada URL.');
            $this->assertSame('pending', $lamaran->status);
            $this->assertNull($lamaran->selection_note);
            $this->assertSame(0, $this->jumlahLamaranProyek($idB));
            $this->assertNull($this->lamaranDb($kandidatLain['email'], $idA));
        });
    }
}
