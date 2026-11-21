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
 * TC-PBI16-014 (sheet PBI-16, Test_Case_ProyekKita_PBI16_PBI17_PBI18_Revisi.xlsx).
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
#[Group('TC-PBI16-014')]
class TC_PBI16_014_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    //use UsesDuskTestDatabase;

    /**
     * TC-PBI16-014 - Proyek tidak ditemukan.
     *
     * Skenario ini tidak membutuhkan proyek (ID 9999 harus tidak ada), sehingga tidak ada langkah UMKM/Admin.
     * Bila ID 9999 kebetulan sudah dipakai di database persistent, dipakai ID lain yang dijamin tidak ada.
     */
    public function test_TC_PBI16_014_proyek_tidak_ditemukan(): void
    {
        $this->browse(function (Browser $browser) {
            // Prasyarat: Kandidat default dari AdminSeeder (read-only).
            $this->pastikanDataDefaultPbi16();
            $kandidat = $this->kandidatDefault();
            $id = $this->idTidakAda('projects', 9999);

            // Login Kandidat lewat halaman /login.
            $this->masukSebagaiKandidat($browser, $kandidat);

            // Langkah uji sesuai Excel.
            $browser->visit("/kandidat/proyek/{$id}");
            $this->assertHalamanGalat($browser, 404, 'Not Found');

            $browser->visit('/kandidat/lamaran');
            $this->kirimFormManipulasi($browser, 'POST', "/kandidat/proyek/{$id}/lamaran", [['message', self::PESAN_VALID]]);
            $this->assertHalamanGalat($browser, 404, 'Not Found');

            $this->assertSame(0, $this->jumlahLamaranProyek($id));
        });
    }
}
