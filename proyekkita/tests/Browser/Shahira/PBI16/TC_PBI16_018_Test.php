<?php

namespace Tests\Browser\PBI16;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\DuskTestCase;
use Tests\Concerns\UsesDuskTestDatabase;

/**
 * TC-PBI16-018 (sheet PBI-16, Test_Case_ProyekKita_PBI16_PBI17_PBI18_Revisi.xlsx).
 *
 * Alur persiapan E2E (semua lewat Chrome, tanpa loginAs/factory/seeder/SQL):
 *   1. Login UMKM, buat proyek lewat UI.  2. Logout UMKM.
 *   3. Login Admin, setujui proyek di Moderasi Proyek.  4. Logout Admin.
 *   5. Login aktor uji lewat /login, lalu jalankan langkah sesuai Excel.
 * Database MySQL test persistent hanya dibaca untuk verifikasi tambahan.
 */
#[Group('e2e-lamaran')]
#[Group('pbi16')]
#[Group('TC-PBI16-018')]
class TC_PBI16_018_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
      use MenjagaDatabaseE2E;
//use UsesDuskTestDatabase;
     

    /**
     * TC-PBI16-018 - Lamaran bersamaan (race) tetap menghasilkan satu lamaran.
     *
     * Kendala Dusk: perintah browser dijalankan berurutan, sehingga dua request tidak dapat dibuat benar-benar
     * bersamaan dan jalur "ditolak oleh unique index" tidak dapat dipaksa dari browser. Test ini memeriksa hasil
     * yang terlihat pengguna dari dua sesi Chrome (kandidat yang sama) yang membuka form sebelum salah satunya
     * mengirim. Jalur unique index tetap dibuktikan Feature test
     * AjukanLamaranTest::test_lamaran_ganda_akibat_request_bersamaan_dicegah_oleh_unique_index.
     */
    public function test_TC_PBI16_018_dua_sesi_mengirim_hanya_satu_lamaran_tersimpan(): void
    {
        $this->browse(function (Browser $browser, Browser $sesiKedua) {
            // Prasyarat akun lewat UI (dipakai ulang bila sudah ada): master data, UMKM terverifikasi, akun kandidat.
            $this->pastikanUmkmTerverifikasi($browser);
            $kandidat = $this->siapkanKandidat($browser, 'TC16018');
            $judul = "Dusk PBI16-018 {$this->kodeUnik()}";

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

            // Persiapan 5: login Kandidat yang sama di dua sesi Chrome.
            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->masukSebagaiKandidat($sesiKedua, $kandidat);

            // Langkah uji: kedua sesi membuka form dan mengetik sebelum ada yang mengirim.
            foreach ([$browser, $sesiKedua] as $sesi) {
                $this->bukaDetailDariEksplor($sesi, $judul)->type('@pesan-lamaran', self::PESAN_VALID);
            }

            $browser->press('@kirim-lamaran')->waitForText(self::MSG_LAMARAN_TERKIRIM);
            $sesiKedua->press('@kirim-lamaran')->waitForText(self::MSG_LAMARAN_GANDA);

            $this->assertSame(1, $this->jumlahLamaranProyek($proyekId));
            $this->assertCount(1, $this->riwayatDb($this->lamaranDb($kandidat['email'], $proyekId)->id), 'Tidak ada riwayat tambahan.');
        });
    }
}
