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
 * TC-PBI16-010 (sheet PBI-16, Test_Case_ProyekKita_PBI16_PBI17_PBI18_Revisi.xlsx).
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
#[Group('TC-PBI16-010')]
class TC_PBI16_010_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    //use UsesDuskTestDatabase;

    /**
     * TC-PBI16-010 - Proyek berstatus Ditutup tidak menerima lamaran.
     *
     * Kendala: aplikasi belum memiliki fitur menutup proyek di UI dan tenggat wajib "after:today",
     * sehingga kondisi ini disimulasikan dengan mengubah SATU kolom pada proyek yang dibuat & disetujui lewat UI.
     * Dilewati secara default; aktifkan dengan DUSK_IZINKAN_SIMULASI_DB=true bila diizinkan dosen.
     */
    #[Group('butuh-simulasi-db')]
    public function test_TC_PBI16_010_proyek_ditutup_tidak_menerima_lamaran(): void
    {
        $this->butuhSimulasiDb('TC-PBI16-010');

        $this->browse(function (Browser $browser) {
            // Prasyarat: Admin, UMKM terverifikasi, Kandidat default, dan master data dari AdminSeeder (read-only).
            $this->pastikanDataDefaultPbi16();
            $kandidat = $this->kandidatDefault();
            $judul = "Dusk PBI16-010 {$this->kodeUnik()}";

            // Persiapan 1: login UMKM dan buat proyek lewat UI (Buat Proyek -> Tinjau & Ajukan -> ajukan ke moderasi).
            $this->masukSebagaiUmkm($browser);
            $proyekId = $this->buatProyek($browser, $judul);

            // Persiapan 2: logout UMKM.
            $this->keluar($browser);

            // Persiapan 3: login Admin, setujui di Moderasi Proyek, pastikan proyek Dipublikasikan.
            $this->masukSebagaiAdmin($browser);
            $this->moderasiProyek($browser, $proyekId, 'approved');
            $this->adminMemastikanProyekDipublikasikan($browser, $proyekId, $judul);

            // Persiapan 4: logout Admin.
            $this->keluar($browser);

            // Kondisi "Ditutup" (belum tersedia di UI).
            $this->simulasikanKondisiProyek($proyekId, ['status' => 'closed']);

            // Persiapan 5: login Kandidat lewat halaman /login.
            $this->masukSebagaiKandidat($browser, $kandidat);

            // Langkah uji sesuai Excel.
            // Proyek Ditutup tidak tampil di Eksplor Proyek, detail dibuka lewat URL.
            $browser->visit("/kandidat/proyek/{$proyekId}")
                ->waitFor('@lamaran-tertutup')
                ->assertSeeIn('@lamaran-tertutup', 'Pengajuan lamaran dinonaktifkan')
                ->assertSeeIn('@lamaran-tertutup', self::MSG_PROYEK_DITUTUP)
                ->assertDisabled('@pesan-lamaran')
                ->assertDisabled('@kirim-lamaran');

            // (Uji HTTP) kirim dari form yang dipaksa aktif.
            $this->aktifkanPaksaFormLamaran($browser)
                ->type('@pesan-lamaran', self::PESAN_VALID)
                ->press('@kirim-lamaran')
                ->waitForText(self::MSG_PROYEK_DITUTUP)
                ->assertDontSee(self::MSG_LAMARAN_TERKIRIM);

            $this->assertSame(0, $this->jumlahLamaranProyek($proyekId));
        });
    }
}
