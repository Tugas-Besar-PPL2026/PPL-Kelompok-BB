<?php

namespace Tests\Browser\PBI16;

use Illuminate\Support\Carbon;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\DuskTestCase;
use Tests\Concerns\UsesDuskTestDatabase;

/**
 * TC-PBI16-027 (sheet PBI-16, Test_Case_ProyekKita_PBI16_PBI17_PBI18_Revisi.xlsx).
 *
 * Alur persiapan E2E (semua lewat Chrome, tanpa loginAs/factory/seeder/SQL):
 *   1. Login UMKM, buat proyek lewat UI.  2. Logout UMKM.
 *   3. Login Admin, setujui proyek di Moderasi Proyek.  4. Logout Admin.
 *   5. Login aktor uji lewat /login, lalu jalankan langkah sesuai Excel.
 * Database MySQL test persistent hanya dibaca untuk verifikasi tambahan.
 */
#[Group('e2e-lamaran')]
#[Group('pbi16')]
#[Group('TC-PBI16-027')]
class TC_PBI16_027_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use UsesDuskTestDatabase;

    /**
     * TC-PBI16-027 - Tenggat lewat setelah halaman dibuka, lalu form dikirim.
     *
     * Kendala: aplikasi belum memiliki fitur menutup proyek di UI dan tenggat wajib "after:today",
     * sehingga kondisi ini disimulasikan dengan mengubah SATU kolom pada proyek yang dibuat & disetujui lewat UI.
     * Dilewati secara default; aktifkan dengan DUSK_IZINKAN_SIMULASI_DB=true bila diizinkan dosen.
     */
    #[Group('butuh-simulasi-db')]
    public function test_TC_PBI16_027_tenggat_lewat_setelah_halaman_dibuka(): void
    {
        $this->butuhSimulasiDb('TC-PBI16-027');

        $this->browse(function (Browser $browser) {
            // Prasyarat akun lewat UI (dipakai ulang bila sudah ada): master data, UMKM terverifikasi, akun kandidat.
            $this->pastikanUmkmTerverifikasi($browser);
            $kandidat = $this->siapkanKandidat($browser, 'TC16027');
            $judul = "Dusk PBI16-027 {$this->kodeUnik()}";

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

            // Persiapan 5: login Kandidat lewat halaman /login.
            $this->masukSebagaiKandidat($browser, $kandidat);

            // Langkah uji sesuai Excel.
            $this->bukaDetailDariEksplor($browser, $judul);

            // (Penguji lain) mengubah tenggat menjadi kemarin - disimulasikan karena form proyek mewajibkan after:today.
            $this->simulasikanKondisiProyek($proyekId, ['application_deadline' => Carbon::yesterday()->toDateString()]);

            $browser->type('@pesan-lamaran', self::PESAN_VALID)
                ->press('@kirim-lamaran')
                ->waitForText(self::MSG_TENGGAT_LEWAT)
                ->assertSeeIn('@lamaran-tertutup', 'Pengajuan lamaran dinonaktifkan')
                ->assertDisabled('@kirim-lamaran');

            $this->assertSame(0, $this->jumlahLamaranProyek($proyekId));
        });
    }
}
