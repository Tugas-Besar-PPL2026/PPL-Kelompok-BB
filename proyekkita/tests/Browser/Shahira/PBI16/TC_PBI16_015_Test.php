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
 * TC-PBI16-015 (sheet PBI-16, Test_Case_ProyekKita_PBI16_PBI17_PBI18_Revisi.xlsx).
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
#[Group('TC-PBI16-015')]
class TC_PBI16_015_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    //use UsesDuskTestDatabase;

    /**
     * TC-PBI16-015 - Kandidat tidak dapat melamar dua kali pada proyek yang sama.
     */
    public function test_TC_PBI16_015_lamaran_ganda_ditolak(): void
    {
        $this->browse(function (Browser $browser) {
            // Prasyarat: Admin, UMKM terverifikasi, Kandidat default, dan master data dari AdminSeeder (read-only).
            $this->pastikanDataDefaultPbi16();
            $kandidat = $this->kandidatDefault();
            $judul = "Dusk PBI16-015 {$this->kodeUnik()}";

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

            // Persiapan 5a: kandidat melamar pertama kali lewat UI (precondition "sudah pernah melamar"), lalu logout.
            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaDetailDariEksplor($browser, $judul)
                ->type('@pesan-lamaran', self::PESAN_VALID)
                ->press('@kirim-lamaran')
                ->waitForText(self::MSG_LAMARAN_TERKIRIM);
            $this->keluar($browser);

            // Persiapan 5: login Kandidat lewat halaman /login.
            $this->masukSebagaiKandidat($browser, $kandidat);

            // Langkah uji sesuai Excel.
            $this->bukaDetailDariEksplor($browser, $judul)
                ->assertSeeIn('@status-lamaran', 'Lamaran Anda sudah terkirim')
                ->assertSeeIn('@status-lamaran', 'Dikirim pada')
                ->assertSeeIn('@status-lamaran', 'Menunggu Keputusan')
                ->assertSeeIn('@kirim-lamaran', 'Lamaran Terkirim')
                ->assertDisabled('@kirim-lamaran')
                ->assertDisabled('@pesan-lamaran');

            // (Uji HTTP) lamaran kedua dengan pesan berbeda.
            $this->aktifkanPaksaFormLamaran($browser)
                ->type('@pesan-lamaran', self::PESAN_VALID.' Pesan kedua.')
                ->press('@kirim-lamaran')
                ->waitForText(self::MSG_LAMARAN_GANDA);

            $this->assertSame(1, $this->jumlahLamaranProyek($proyekId));
            $lamaran = $this->lamaranDb($kandidat['email'], $proyekId);
            $this->assertSame(self::PESAN_VALID, $lamaran->message, 'Pesan lamaran pertama tidak boleh tertimpa.');
            $this->assertCount(1, $this->riwayatDb($lamaran->id));
        });
    }
}
