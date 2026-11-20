<?php

namespace Tests\Browser;

use App\Enums\ProjectStatus;
use App\Models\Notification;
use App\Models\Project;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Concerns\DataUjiProyekBrowser;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

/**
 * PBI-11 - Mengajukan proyek untuk moderasi. Satu test untuk setiap test case TC-PBI11-001 sampai TC-PBI11-009.
 * Semua langkah dilakukan seperti pengguna: masuk lewat halaman Masuk, berpindah halaman lewat menu dan tombol,
 * lalu mengetik isian dengan data tetap (lihat Tests\Concerns\DataUjiProyekBrowser).
 */
class PengajuanModerasiTest extends DuskTestCase
{
    use DataUjiProyekBrowser;
    use UsesDuskTestDatabase;

    private const JUDUL = 'Redesain Katalog Digital & Label Kemasan Kopi Nusantara';

    private const JUDUL_AWAL = 'Redesain Label Kemasan Kopi Arabika Gayo';

    private const SYARAT = ['verified', 'complete', 'deadline', 'target', 'terms'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->siapkanDataMaster();
    }

    /** TC-PBI11-001 */
    public function test_tc_pbi11_001_tombol_tinjau_dan_ajukan_membuka_pratinjau_dan_syarat(): void
    {
        $umkm = $this->buatUmkmKopiNusantara();

        $this->browse(function (Browser $browser) use ($umkm) {
            $this->isiDrafBaru($browser, $umkm);
            $this->tekan($browser, '@tinjau-ajukan')
                ->waitForText('Syarat Pengajuan', 15)
                ->assertPathEndsWith('/tinjau')
                ->assertSee('Draf proyek disimpan. Periksa kembali sebelum mengajukan.')
                ->assertSee(self::JUDUL)
                ->assertSee('Desain Grafis & Branding')
                ->assertSee('Desain Kemasan')
                ->assertSee('Uang muka setelah kesepakatan berstatus aktif')
                ->assertSee('Rp2.500.000')
                ->assertSeeIn('@status-proyek', 'Draf')
                ->assertDisabled('@ajukan-moderasi');

            foreach (self::SYARAT as $syarat) {
                $browser->assertAttributeContains('@syarat-' . $syarat, 'class', 'ok');
            }
        });

        $this->assertSame(ProjectStatus::Draft, Project::where('title', self::JUDUL)->sole()->status);
    }

    /** TC-PBI11-002 */
    public function test_tc_pbi11_002_mengajukan_draf_dan_admin_menerima_notifikasi(): void
    {
        $umkm = $this->buatUmkmKopiNusantara();
        $admins = $this->buatDuaAdmin();

        $this->browse(function (Browser $browser) use ($umkm) {
            $this->isiDrafBaru($browser, $umkm);
            $this->tekan($browser, '@tinjau-ajukan')->waitForText('Syarat Pengajuan', 15)->check('@pernyataan');
            $this->klikAjukan($browser)
                ->waitForText('Proyek diajukan dan menunggu moderasi admin.', 15)
                ->assertSeeIn('@status-proyek', 'Menunggu Moderasi')
                ->assertMissing('@ajukan-moderasi')
                ->assertMissing('@kembali-ubah')
                ->assertSeeIn('@info-sudah-diajukan', 'tidak dapat diubah atau diajukan ulang');
        });

        $project = Project::where('title', self::JUDUL)->sole();
        $this->assertSame(ProjectStatus::PendingReview, $project->status);
        $this->assertNotificationPerAdmin($admins, $project);
    }

    /** TC-PBI11-003 */
    public function test_tc_pbi11_003_tombol_kembali_ubah_draf_membuka_form_draf(): void
    {
        $umkm = $this->buatUmkmKopiNusantara();
        $project = $this->buatProyekAwal($umkm);

        $this->browse(function (Browser $browser) use ($umkm, $project) {
            $this->lanjutkanDraf($this->masuk($browser, $umkm), $project);
            $this->tekan($browser, '@tinjau-ajukan')->waitForText('Syarat Pengajuan', 15);

            $browser->clickAndWaitForReload('@kembali-ubah')
                ->assertPathIs("/umkm/proyek/{$project->id}/ubah")
                ->assertInputValue('title', self::JUDUL_AWAL)
                ->assertInputValue('quota', '2');
        });

        $this->assertSame(ProjectStatus::Draft, $project->fresh()->status);
    }

    /** TC-PBI11-004 */
    public function test_tc_pbi11_004_proyek_perlu_perbaikan_diajukan_ulang(): void
    {
        $umkm = $this->buatUmkmKopiNusantara();
        $admins = $this->buatDuaAdmin();
        $project = $this->buatProyekAwal($umkm, ['status' => ProjectStatus::RevisionRequested]);

        $this->browse(function (Browser $browser) use ($umkm, $project) {
            $this->perbaikiProyek($this->masuk($browser, $umkm), $project)
                ->type('description', 'Kopi Nusantara membutuhkan desainer untuk memperbarui label kemasan kopi Arabika Gayo ukuran 250 gram dan 1 kilogram. File akhir wajib dalam format AI dan PDF siap cetak.');
            $this->tekan($browser, '@tinjau-ajukan')
                ->waitForText('Syarat Pengajuan', 15)
                ->assertSeeIn('@status-proyek', 'Perlu Perbaikan')
                ->check('@pernyataan');
            $this->klikAjukan($browser)
                ->waitForText('Proyek diajukan dan menunggu moderasi admin.', 15)
                ->assertSeeIn('@status-proyek', 'Menunggu Moderasi');
        });

        $this->assertSame(ProjectStatus::PendingReview, $project->fresh()->status);
        $this->assertNotificationPerAdmin($admins, $project->fresh());
    }

    /** TC-PBI11-005 */
    public function test_tc_pbi11_005_tombol_ajukan_aktif_hanya_saat_pernyataan_dicentang(): void
    {
        $umkm = $this->buatUmkmKopiNusantara();
        $project = $this->buatProyekAwal($umkm);

        $this->browse(function (Browser $browser) use ($umkm, $project) {
            $this->lanjutkanDraf($this->masuk($browser, $umkm), $project);
            $this->tekan($browser, '@tinjau-ajukan')
                ->waitForText('Syarat Pengajuan', 15)
                ->assertDisabled('@ajukan-moderasi')
                ->check('@pernyataan')
                ->assertEnabled('@ajukan-moderasi')
                ->uncheck('@pernyataan')
                ->assertDisabled('@ajukan-moderasi');
        });

        $this->assertSame(ProjectStatus::Draft, $project->fresh()->status);
        $this->assertSame(0, Notification::count());
    }

    /** TC-PBI11-006 */
    public function test_tc_pbi11_006_umkm_belum_terverifikasi_tidak_bisa_mengajukan(): void
    {
        $umkm = $this->buatUmkmRotiAndiBelumTerverifikasi();
        $project = $this->buatProyekAwal($umkm, ['title' => 'Desain Kemasan Roti Manis Roti Andi']);

        $this->browse(function (Browser $browser) use ($umkm, $project) {
            $this->lanjutkanDraf($this->masuk($browser, $umkm), $project);
            $this->tekan($browser, '@tinjau-ajukan')
                ->waitForText('Syarat Pengajuan', 15)
                ->assertAttributeContains('@syarat-verified', 'class', 'no')
                ->assertSeeIn('@syarat-verified', 'Menunggu verifikasi admin')
                ->assertDisabled('@pernyataan')
                ->assertDisabled('@ajukan-moderasi')
                ->assertSee('Lengkapi syarat yang belum terpenuhi');
        });

        $this->assertSame(ProjectStatus::Draft, $project->fresh()->status);
    }

    /** TC-PBI11-007 */
    public function test_tc_pbi11_007_draf_dengan_tenggat_lewat_tidak_bisa_ditinjau(): void
    {
        $umkm = $this->buatUmkmKopiNusantara();
        $project = $this->buatProyekAwal($umkm, ['application_deadline' => now()->subDay()->toDateString()]);

        $this->browse(function (Browser $browser) use ($umkm, $project) {
            $this->lanjutkanDraf($this->masuk($browser, $umkm), $project);
            $this->tekan($browser, '@tinjau-ajukan')
                ->waitForText('Tenggat lamaran paling cepat besok.', 15)
                ->assertPathIs("/umkm/proyek/{$project->id}/ubah")
                ->assertDontSee('Syarat Pengajuan');
        });

        $this->assertSame(ProjectStatus::Draft, $project->fresh()->status);
    }

    /** TC-PBI11-008 */
    public function test_tc_pbi11_008_draf_dengan_target_sama_dengan_tenggat_tidak_bisa_ditinjau(): void
    {
        $umkm = $this->buatUmkmKopiNusantara();
        $tenggat = now()->addDays(10)->toDateString();
        $project = $this->buatProyekAwal($umkm, ['application_deadline' => $tenggat, 'target_completion_date' => $tenggat]);

        $this->browse(function (Browser $browser) use ($umkm, $project) {
            $this->lanjutkanDraf($this->masuk($browser, $umkm), $project);
            $this->tekan($browser, '@tinjau-ajukan')
                ->waitForText('Target penyelesaian harus setelah tenggat lamaran.', 15)
                ->assertPathIs("/umkm/proyek/{$project->id}/ubah")
                ->assertDontSee('Syarat Pengajuan');
        });

        $this->assertSame(ProjectStatus::Draft, $project->fresh()->status);
    }

    /** TC-PBI11-009 */
    public function test_tc_pbi11_009_pengajuan_ganda_dari_dua_jendela_ditolak(): void
    {
        $umkm = $this->buatUmkmKopiNusantara();
        $admins = $this->buatDuaAdmin();
        $project = $this->buatProyekAwal($umkm);

        $this->browse(function (Browser $jendelaPertama, Browser $jendelaKedua) use ($umkm, $project) {
            foreach ([$jendelaPertama, $jendelaKedua] as $jendela) {
                $this->lanjutkanDraf($this->masuk($jendela, $umkm), $project);
                $this->tekan($jendela, '@tinjau-ajukan')->waitForText('Syarat Pengajuan', 15)->check('@pernyataan');
            }

            $this->klikAjukan($jendelaPertama)->waitForText('Proyek diajukan dan menunggu moderasi admin.', 15);
            $this->klikAjukan($jendelaKedua)->waitForText('Proyek ini sudah diajukan atau sudah diproses admin.', 15);
        });

        $this->assertSame(ProjectStatus::PendingReview, $project->fresh()->status);
        $this->assertNotificationPerAdmin($admins, $project->fresh());
    }

    /** Masuk sebagai UMKM, membuka Buat Proyek lewat menu, lalu mengetik draf lengkap dengan dua termin Rp1.250.000. */
    private function isiDrafBaru(Browser $browser, User $umkm): Browser
    {
        $this->bukaFormBuatProyek($this->masuk($browser, $umkm));
        $this->isiInformasiProyek($browser);
        $browser->radio('payment_method', 'bertahap');
        $this->isiNominalTermin($browser, 1, '1250000');
        $this->isiKondisiTermin($browser, 1, 'Uang muka setelah kesepakatan berstatus aktif');
        $this->isiNominalTermin($browser, 2, '1250000');

        return $this->isiKondisiTermin($browser, 2, 'Pelunasan setelah hasil akhir disetujui UMKM');
    }

    /** Tombol Ajukan ada di kartu samping, jadi digulung ke tengah layar dulu agar tidak tertutup navbar. */
    private function klikAjukan(Browser $browser): Browser
    {
        $browser->script("document.getElementById('tombol-ajukan').scrollIntoView({block: 'center'});");

        return $browser->press('@ajukan-moderasi');
    }

    /** Setiap admin menerima tepat satu notifikasi untuk proyek ini. */
    private function assertNotificationPerAdmin(array $admins, Project $project): void
    {
        foreach ($admins as $admin) {
            $this->assertSame(1, Notification::where('user_id', $admin->id)
                ->where('related_id', $project->id)
                ->where('message', 'Proyek baru menunggu moderasi: ' . $project->title)
                ->count());
        }
    }
}
