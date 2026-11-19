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
 * TC-PBI17-016 (sheet PBI-17, Test_Case_ProyekKita_PBI16_PBI17_PBI18_Revisi.xlsx).
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
#[Group('TC-PBI17-016')]
class TC_PBI17_016_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
      use MenjagaDatabaseE2E;
//use UsesDuskTestDatabase;
    use PersiapanLamaranE2E;
     

    /**
     * TC-PBI17-016 - Keadaan kosong: kandidat belum pernah melamar (dengan profil dan tanpa profil).
     *
     * Tidak membutuhkan proyek, sehingga tidak ada langkah UMKM/Admin.
     */
    public function test_TC_PBI17_016_keadaan_kosong(): void
    {
        $this->browse(function (Browser $browser) {
            // Prasyarat akun lewat UI: skenario A (dengan profil, belum melamar), skenario B (tanpa profil).
            $kandidatA = $this->siapkanKandidat($browser, 'TC17016A');
            $kandidatB = $this->siapkanKandidat($browser, 'TC17016B', 'tanpa-profil');

            foreach (['skenario A' => $kandidatA, 'skenario B' => $kandidatB] as $skenario => $kandidat) {
                // Login aktor penguji (Kandidat) lewat /login.
                $this->masukSebagaiKandidat($browser, $kandidat);

                // Klik menu Lamaran Saya, amati keadaan kosong.
                $this->bukaLamaranSaya($browser)
                    ->assertSee('Belum ada lamaran')
                    ->assertSee('Anda belum mengajukan lamaran pada proyek mana pun.')
                    ->assertDontSee('Server Error');
                $this->assertSame([], $this->urutanJudulKartu($browser), "Tidak ada kartu lamaran ({$skenario}).");

                // Klik tautan 'Eksplor proyek'.
                $this->klikTautan($browser, 'Eksplor proyek')->assertPathIs('/kandidat/proyek');

                $this->keluar($browser);
            }
        });
    }
}
