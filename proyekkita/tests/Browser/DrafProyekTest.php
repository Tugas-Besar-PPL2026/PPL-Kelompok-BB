<?php

namespace Tests\Browser;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Laravel\Dusk\Browser;
use Tests\Concerns\DataUjiProyekBrowser;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

/**
 * PBI-9 - Membuat dan memperbarui draf proyek. Satu test untuk setiap test case TC-PBI9-001 sampai TC-PBI9-008.
 * Semua langkah dilakukan seperti pengguna: masuk lewat halaman Masuk, berpindah halaman lewat menu dan tombol,
 * lalu mengetik isian dengan data tetap (lihat Tests\Concerns\DataUjiProyekBrowser).
 */
class DrafProyekTest extends DuskTestCase
{
    use DataUjiProyekBrowser;
    use UsesDuskTestDatabase;

    private const JUDUL = 'Redesain Katalog Digital & Label Kemasan Kopi Nusantara';

    private const KONDISI_SEKALI = 'Dibayar penuh setelah file desain final diserahkan dan disetujui';

    protected function setUp(): void
    {
        parent::setUp();

        $this->siapkanDataMaster();
    }

    /** TC-PBI9-001 */
    public function test_tc_pbi9_001_umkm_menyimpan_draf_proyek_baru(): void
    {
        $umkm = $this->buatUmkmKopiNusantara();

        $this->browse(function (Browser $browser) use ($umkm) {
            $this->bukaFormBuatProyek($this->masuk($browser, $umkm));
            $this->isiInformasiProyek($browser);
            $browser->radio('payment_method', 'sekali');
            $this->isiKondisiTermin($browser, 1, self::KONDISI_SEKALI);

            $this->tekan($browser, '@simpan-draf')
                ->waitForText('Draf proyek disimpan.', 15)
                ->assertPathEndsWith('/ubah')
                ->assertInputValue('title', self::JUDUL);
        });

        $project = Project::where('title', self::JUDUL)->sole();
        $this->assertSame(ProjectStatus::Draft, $project->status);
        $this->assertSame($umkm->umkmProfile->id, $project->umkm_profile_id);
        $this->assertSame(2, $project->quota);
        $this->assertEqualsCanonicalizing(['Desain Kemasan', 'Figma'], $project->skills->pluck('name')->all());
    }

    /** TC-PBI9-002 */
    public function test_tc_pbi9_002_lanjutkan_draf_menampilkan_data_tersimpan(): void
    {
        $umkm = $this->buatUmkmKopiNusantara();
        $project = $this->buatProyekAwal($umkm, [
            'title' => 'Katalog Digital Kopi Gayo',
            'description' => 'Menyusun katalog digital untuk tiga varian kopi Gayo yang akan dibagikan ke reseller.',
            'quota' => 4,
        ]);

        $this->browse(function (Browser $browser) use ($umkm, $project) {
            $this->lanjutkanDraf($this->masuk($browser, $umkm), $project)
                ->assertInputValue('title', 'Katalog Digital Kopi Gayo')
                ->assertSelected('category_id', (string) $this->kategori('Desain Grafis & Branding')->id)
                ->assertInputValue('description', 'Menyusun katalog digital untuk tiga varian kopi Gayo yang akan dibagikan ke reseller.')
                ->assertInputValue('quota', '4')
                ->assertInputValue('total_compensation', '3000000');
        });
    }

    /** TC-PBI9-003 */
    public function test_tc_pbi9_003_umkm_mengubah_judul_kuota_dan_keterampilan_draf(): void
    {
        $umkm = $this->buatUmkmKopiNusantara();
        $project = $this->buatProyekAwal($umkm, [], null, ['Figma']);

        $this->browse(function (Browser $browser) use ($umkm, $project) {
            $this->lanjutkanDraf($this->masuk($browser, $umkm), $project)
                ->type('title', 'Redesain Label Kemasan Kopi Gayo Edisi Ramadan')
                ->type('quota', '3')
                ->click('@skill-' . $this->keterampilan('Figma')->id)
                ->click('@skill-' . $this->keterampilan('Copywriting')->id);

            $this->tekan($browser, '@simpan-draf')
                ->waitForText('Draf proyek diperbarui.', 15)
                ->assertInputValue('title', 'Redesain Label Kemasan Kopi Gayo Edisi Ramadan');
        });

        $project->refresh();
        $this->assertSame('Redesain Label Kemasan Kopi Gayo Edisi Ramadan', $project->title);
        $this->assertSame(3, $project->quota);
        $this->assertSame(ProjectStatus::Draft, $project->status);
        $this->assertSame(['Copywriting'], $project->skills->pluck('name')->all());
    }

    /** TC-PBI9-004 */
    public function test_tc_pbi9_004_proyek_perlu_perbaikan_dibuka_dan_disimpan_ulang(): void
    {
        $umkm = $this->buatUmkmKopiNusantara();
        $project = $this->buatProyekAwal($umkm, ['status' => ProjectStatus::RevisionRequested]);

        $this->browse(function (Browser $browser) use ($umkm, $project) {
            $this->perbaikiProyek($this->masuk($browser, $umkm), $project)
                ->assertInputValue('title', 'Redesain Label Kemasan Kopi Arabika Gayo')
                ->type('description', 'Kopi Nusantara membutuhkan desainer untuk memperbarui label kemasan kopi Arabika Gayo ukuran 250 gram dan 1 kilogram. File akhir wajib dalam format AI dan PDF siap cetak.');

            $this->tekan($browser, '@simpan-draf')->waitForText('Draf proyek diperbarui.', 15);
        });

        $project->refresh();
        $this->assertStringContainsString('File akhir wajib dalam format AI dan PDF siap cetak.', $project->description);
        $this->assertSame(ProjectStatus::RevisionRequested, $project->status);
    }

    /** TC-PBI9-005 */
    public function test_tc_pbi9_005_semua_kolom_wajib_kosong_ditolak(): void
    {
        $umkm = $this->buatUmkmKopiNusantara();

        $this->browse(function (Browser $browser) use ($umkm) {
            $this->bukaFormBuatProyek($this->masuk($browser, $umkm))->clear('quota');

            $this->tekan($browser, '@simpan-draf')
                ->waitForText('Judul proyek wajib diisi.', 15)
                ->assertSee('Kategori wajib diisi.')
                ->assertSee('Keterampilan wajib diisi.')
                ->assertSee('Deskripsi pekerjaan wajib diisi.')
                ->assertSee('Hasil yang diharapkan wajib diisi.')
                ->assertSee('Tenggat lamaran wajib diisi.')
                ->assertSee('Target penyelesaian wajib diisi.')
                ->assertSee('Kuota kandidat wajib diisi.')
                ->assertSee('Total kompensasi wajib diisi.')
                ->assertPathIs('/umkm/proyek/buat');
        });

        $this->assertSame(0, Project::count());
    }

    /** TC-PBI9-006 */
    public function test_tc_pbi9_006_tenggat_hari_ini_dan_target_sebelum_tenggat_ditolak(): void
    {
        $umkm = $this->buatUmkmKopiNusantara();

        $this->browse(function (Browser $browser) use ($umkm) {
            $this->bukaFormBuatProyek($this->masuk($browser, $umkm));
            $this->isiInformasiProyek($browser, [
                'tenggat' => now()->toDateString(),
                'target' => now()->subDay()->toDateString(),
            ]);
            $browser->radio('payment_method', 'sekali');
            $this->isiKondisiTermin($browser, 1, self::KONDISI_SEKALI);

            $this->tekan($browser, '@simpan-draf')
                ->waitForText('Tenggat lamaran paling cepat besok.', 15)
                ->assertSee('Target penyelesaian harus setelah tenggat lamaran.')
                ->assertInputValue('title', self::JUDUL);
        });

        $this->assertSame(0, Project::count());
    }

    /** TC-PBI9-007 */
    public function test_tc_pbi9_007_judul_tidak_bisa_lebih_dari_150_karakter(): void
    {
        $umkm = $this->buatUmkmKopiNusantara();
        $judul151 = 'Redesain Label Kemasan, Katalog Produk Digital, Banner Marketplace, dan Konten Media Sosial untuk Tiga Varian Biji Kopi Unggulan Kopi Nusantara Bandung';
        $this->assertSame(151, mb_strlen($judul151));

        $this->browse(function (Browser $browser) use ($umkm, $judul151) {
            $this->bukaFormBuatProyek($this->masuk($browser, $umkm));
            $this->isiInformasiProyek($browser, ['judul' => $judul151]);
            $browser->radio('payment_method', 'sekali');
            $this->isiKondisiTermin($browser, 1, self::KONDISI_SEKALI);

            // Kolom judul berhenti menerima ketikan di karakter ke-150.
            $this->assertSame(mb_substr($judul151, 0, 150), $browser->inputValue('title'));

            $this->tekan($browser, '@simpan-draf')->waitForText('Draf proyek disimpan.', 15);
        });

        $this->assertSame(mb_substr($judul151, 0, 150), Project::sole()->title);
    }

    /** TC-PBI9-008 */
    public function test_tc_pbi9_008_batas_kuota_dan_total_kompensasi(): void
    {
        $umkm = $this->buatUmkmKopiNusantara();

        $this->browse(function (Browser $browser) use ($umkm) {
            $this->bukaFormBuatProyek($this->masuk($browser, $umkm));
            $this->isiInformasiProyek($browser, ['kuota' => '1001', 'total' => '10000000000']);
            $browser->radio('payment_method', 'sekali');
            $this->isiKondisiTermin($browser, 1, self::KONDISI_SEKALI);

            // Browser menolak mengirim form dan menandai kolom yang melewati batas.
            $this->tekan($browser, '@simpan-draf')
                ->pause(1000)
                ->assertPathIs('/umkm/proyek/buat')
                ->assertDontSee('Draf proyek disimpan.');
            $this->assertFalse($browser->script("return document.getElementById('quota').checkValidity();")[0]);
            $this->assertFalse($browser->script("return document.getElementById('total_compensation').checkValidity();")[0]);

            $browser->type('quota', '1000')->type('total_compensation', '9999999999');
            $this->tekan($browser, '@simpan-draf')->waitForText('Draf proyek disimpan.', 15);
        });

        $project = Project::sole();
        $this->assertSame(1000, $project->quota);
        $this->assertEquals(9999999999, (float) $project->total_compensation);
    }
}
