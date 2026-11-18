<?php

namespace Tests\Browser;

use App\Models\Project;
use Laravel\Dusk\Browser;
use Tests\Concerns\DataUjiProyekBrowser;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

/**
 * PBI-10 - Mengatur skema pembayaran proyek. Satu test untuk setiap test case TC-PBI10-001 sampai TC-PBI10-012.
 * Semua langkah dilakukan seperti pengguna: masuk lewat halaman Masuk, berpindah halaman lewat menu dan tombol,
 * lalu mengetik isian dengan data tetap (lihat Tests\Concerns\DataUjiProyekBrowser).
 */
class SkemaPembayaranTest extends DuskTestCase
{
    use DataUjiProyekBrowser;
    use UsesDuskTestDatabase;

    private const JUDUL = 'Redesain Katalog Digital & Label Kemasan Kopi Nusantara';

    protected function setUp(): void
    {
        parent::setUp();

        $this->siapkanDataMaster();
    }

    /** TC-PBI10-001 */
    public function test_tc_pbi10_001_menyimpan_skema_bertahap_dua_termin(): void
    {
        $umkm = $this->buatUmkmKopiNusantara();

        $this->browse(function (Browser $browser) use ($umkm) {
            $this->bukaFormBuatProyek($this->masuk($browser, $umkm));
            $this->isiInformasiProyek($browser);
            $this->isiDuaTermin($browser, '1250000', '1250000');

            $browser->assertSeeIn('@status-termin', 'Jumlah termin sesuai total kompensasi');
            $this->tekan($browser, '@simpan-draf')->waitForText('Draf proyek disimpan.', 15);
        });

        $terms = Project::where('title', self::JUDUL)->sole()->paymentTerms()->orderBy('term_number')->get();
        $this->assertSame([1, 2], $terms->pluck('term_number')->all());
        $this->assertEquals([1250000, 1250000], $terms->pluck('amount')->map(fn ($a) => (float) $a)->all());
        $this->assertSame(['Uang muka setelah kesepakatan berstatus aktif', 'Pelunasan setelah hasil akhir disetujui UMKM'], $terms->pluck('condition')->all());
    }

    /** TC-PBI10-002 */
    public function test_tc_pbi10_002_menyimpan_skema_sekali_bayar(): void
    {
        $umkm = $this->buatUmkmKopiNusantara();

        $this->browse(function (Browser $browser) use ($umkm) {
            $this->bukaFormBuatProyek($this->masuk($browser, $umkm));
            $this->isiInformasiProyek($browser);
            $browser->radio('payment_method', 'sekali');
            $this->isiKondisiTermin($browser, 1, 'Pelunasan setelah hasil akhir disetujui UMKM');

            $this->tekan($browser, '@simpan-draf')->waitForText('Draf proyek disimpan.', 15);
        });

        $term = Project::where('title', self::JUDUL)->sole()->paymentTerms()->sole();
        $this->assertSame(1, $term->term_number);
        $this->assertEquals(2500000, (float) $term->amount);
        $this->assertSame('Pelunasan setelah hasil akhir disetujui UMKM', $term->condition);
    }

    /** TC-PBI10-003 */
    public function test_tc_pbi10_003_sekali_bayar_mengunci_nominal_dan_bertahap_menambah_baris(): void
    {
        $umkm = $this->buatUmkmKopiNusantara();

        $this->browse(function (Browser $browser) use ($umkm) {
            $this->bukaFormBuatProyek($this->masuk($browser, $umkm))
                ->type('total_compensation', '2500000')
                ->radio('payment_method', 'sekali');

            $this->assertCount(1, $browser->elements('#tabel-termin tbody tr'));
            $browser->assertInputValue('#tabel-termin tbody tr:nth-child(1) .term-amount', '2500000')
                ->assertAttribute('#tabel-termin tbody tr:nth-child(1) .term-amount', 'readonly', 'true')
                ->assertMissing('@tambah-termin');

            $browser->radio('payment_method', 'bertahap');

            $this->assertCount(2, $browser->elements('#tabel-termin tbody tr'));
            $browser->assertVisible('@tambah-termin');
        });
    }

    /** TC-PBI10-004 */
    public function test_tc_pbi10_004_termin_tersimpan_tampil_saat_lanjutkan_draf(): void
    {
        $umkm = $this->buatUmkmKopiNusantara();
        $project = $this->buatProyekAwal($umkm);

        $this->browse(function (Browser $browser) use ($umkm, $project) {
            $this->lanjutkanDraf($this->masuk($browser, $umkm), $project)
                ->assertRadioSelected('payment_method', 'bertahap')
                ->assertInputValue('#tabel-termin tbody tr:nth-child(1) [name$="[condition]"]', 'Uang muka setelah kesepakatan kerja disetujui kedua pihak')
                ->assertInputValue('#tabel-termin tbody tr:nth-child(1) .term-amount', '1500000')
                ->assertInputValue('#tabel-termin tbody tr:nth-child(2) [name$="[condition]"]', 'Pelunasan setelah file desain final diserahkan')
                ->assertInputValue('#tabel-termin tbody tr:nth-child(2) .term-amount', '1500000')
                ->assertSeeIn('@status-termin', 'Jumlah termin sesuai total kompensasi');
        });
    }

    /** TC-PBI10-005 */
    public function test_tc_pbi10_005_menambah_termin_ketiga(): void
    {
        $umkm = $this->buatUmkmKopiNusantara();
        $project = $this->buatProyekAwal($umkm);

        $this->browse(function (Browser $browser) use ($umkm, $project) {
            $this->lanjutkanDraf($this->masuk($browser, $umkm), $project)->click('@tambah-termin');
            $this->isiNominalTermin($browser, 1, '1000000');
            $this->isiNominalTermin($browser, 2, '1000000');
            $this->isiNominalTermin($browser, 3, '1000000');
            $this->isiKondisiTermin($browser, 2, 'Setelah draf desain pertama dikirim');
            $this->isiKondisiTermin($browser, 3, 'Pelunasan setelah file desain final diserahkan');

            $browser->assertSeeIn('@status-termin', 'Jumlah termin sesuai total kompensasi');
            $this->tekan($browser, '@simpan-draf')->waitForText('Draf proyek diperbarui.', 15);
        });

        $terms = $project->paymentTerms()->orderBy('term_number')->get();
        $this->assertSame([1, 2, 3], $terms->pluck('term_number')->all());
        $this->assertSame('Setelah draf desain pertama dikirim', $terms[1]->condition);
    }

    /** TC-PBI10-006 */
    public function test_tc_pbi10_006_mengganti_bertahap_menjadi_sekali_bayar(): void
    {
        $umkm = $this->buatUmkmKopiNusantara();
        $project = $this->buatProyekAwal($umkm);

        $this->browse(function (Browser $browser) use ($umkm, $project) {
            $this->lanjutkanDraf($this->masuk($browser, $umkm), $project)->radio('payment_method', 'sekali');
            $this->assertCount(1, $browser->elements('#tabel-termin tbody tr'));
            $this->isiKondisiTermin($browser, 1, 'Pelunasan setelah file desain final diserahkan');

            $this->tekan($browser, '@simpan-draf')->waitForText('Draf proyek diperbarui.', 15);
        });

        $term = $project->paymentTerms()->sole();
        $this->assertSame(1, $term->term_number);
        $this->assertEquals(3000000, (float) $term->amount);
    }

    /** TC-PBI10-007 */
    public function test_tc_pbi10_007_menghapus_termin_tengah(): void
    {
        $umkm = $this->buatUmkmKopiNusantara();
        $project = $this->buatProyekAwal($umkm, ['total_compensation' => 2500000], [
            [1000000, 'Termin A: uang muka setelah kesepakatan aktif'],
            [500000, 'Termin B: setelah draf desain pertama dikirim'],
            [1000000, 'Termin C: pelunasan setelah file final diserahkan'],
        ]);

        $this->browse(function (Browser $browser) use ($umkm, $project) {
            $this->lanjutkanDraf($this->masuk($browser, $umkm), $project)
                ->click('#tabel-termin tbody tr:nth-child(2) .hapus-termin')
                ->type('total_compensation', '2000000');

            $this->assertCount(2, $browser->elements('#tabel-termin tbody tr'));
            $browser->assertSeeIn('@status-termin', 'Jumlah termin sesuai total kompensasi');
            $this->tekan($browser, '@simpan-draf')->waitForText('Draf proyek diperbarui.', 15);
        });

        $terms = $project->paymentTerms()->orderBy('term_number')->get();
        $this->assertSame([1, 2], $terms->pluck('term_number')->all());
        $this->assertSame(['Termin A: uang muka setelah kesepakatan aktif', 'Termin C: pelunasan setelah file final diserahkan'], $terms->pluck('condition')->all());
    }

    /** TC-PBI10-008 */
    public function test_tc_pbi10_008_jumlah_termin_tidak_sama_dengan_total_ditolak(): void
    {
        $umkm = $this->buatUmkmKopiNusantara();

        $this->browse(function (Browser $browser) use ($umkm) {
            $this->bukaFormBuatProyek($this->masuk($browser, $umkm));
            $this->isiInformasiProyek($browser);
            $this->isiDuaTermin($browser, '1000000', '1000000');

            $browser->assertSeeIn('@status-termin', 'Jumlah termin belum sesuai total kompensasi');
            $this->tekan($browser, '@simpan-draf')->waitForText('Jumlah nominal termin harus sama dengan total kompensasi.', 15);
        });

        $this->assertSame(0, Project::count());
    }

    /** TC-PBI10-009 */
    public function test_tc_pbi10_009_bertahap_dengan_satu_termin_ditolak(): void
    {
        $umkm = $this->buatUmkmKopiNusantara();

        $this->browse(function (Browser $browser) use ($umkm) {
            $this->bukaFormBuatProyek($this->masuk($browser, $umkm));
            $this->isiInformasiProyek($browser);
            $browser->radio('payment_method', 'bertahap')
                ->click('#tabel-termin tbody tr:nth-child(2) .hapus-termin');
            $this->isiNominalTermin($browser, 1, '2500000');
            $this->isiKondisiTermin($browser, 1, 'Pelunasan setelah hasil akhir disetujui UMKM');

            $this->tekan($browser, '@simpan-draf')->waitForText('Pembayaran bertahap minimal terdiri dari dua termin.', 15);
        });

        $this->assertSame(0, Project::count());
    }

    /** TC-PBI10-010 */
    public function test_tc_pbi10_010_termin_terakhir_tidak_bisa_dihapus(): void
    {
        $umkm = $this->buatUmkmKopiNusantara();

        $this->browse(function (Browser $browser) use ($umkm) {
            $this->bukaFormBuatProyek($this->masuk($browser, $umkm))
                ->type('total_compensation', '2500000')
                ->radio('payment_method', 'bertahap')
                ->assertEnabled('#tabel-termin tbody tr:nth-child(2) .hapus-termin')
                ->click('#tabel-termin tbody tr:nth-child(2) .hapus-termin')
                ->assertDisabled('#tabel-termin tbody tr:nth-child(1) .hapus-termin');

            $this->assertCount(1, $browser->elements('#tabel-termin tbody tr'));
        });
    }

    /** TC-PBI10-011 */
    public function test_tc_pbi10_011_nominal_nol_dan_kondisi_kosong_ditolak(): void
    {
        $umkm = $this->buatUmkmKopiNusantara();

        $this->browse(function (Browser $browser) use ($umkm) {
            $this->bukaFormBuatProyek($this->masuk($browser, $umkm));
            $this->isiInformasiProyek($browser);
            $browser->radio('payment_method', 'bertahap');
            $this->isiNominalTermin($browser, 1, '0');
            $this->isiKondisiTermin($browser, 1, 'Uang muka setelah kesepakatan berstatus aktif');
            $this->isiNominalTermin($browser, 2, '2500000');
            $this->isiKondisiTermin($browser, 2, 'Pelunasan setelah hasil akhir disetujui UMKM');

            // Nominal 0: browser menolak mengirim form dan menandai kolom nominal termin 1.
            $this->tekan($browser, '@simpan-draf')
                ->pause(1000)
                ->assertPathIs('/umkm/proyek/buat')
                ->assertDontSee('Draf proyek disimpan.');
            $this->assertFalse($browser->script("return document.querySelector('#tabel-termin tbody tr:nth-child(1) .term-amount').checkValidity();")[0]);

            // Nominal diperbaiki, tetapi kondisi termin 2 dikosongkan.
            $this->isiNominalTermin($browser, 1, '1250000');
            $this->isiNominalTermin($browser, 2, '1250000');
            $browser->clear('#tabel-termin tbody tr:nth-child(2) [name$="[condition]"]');

            $this->tekan($browser, '@simpan-draf')->waitForText('Kondisi pembayaran wajib diisi.', 15);
        });

        $this->assertSame(0, Project::count());
    }

    /** TC-PBI10-012 */
    public function test_tc_pbi10_012_kondisi_termin_tidak_bisa_lebih_dari_255_karakter(): void
    {
        $umkm = $this->buatUmkmKopiNusantara();
        $kondisi = str_repeat('Pelunasan dibayar setelah file desain final diserahkan dan disetujui pemilik usaha. ', 4);
        $this->assertGreaterThan(255, mb_strlen($kondisi));

        $this->browse(function (Browser $browser) use ($umkm, $kondisi) {
            $this->bukaFormBuatProyek($this->masuk($browser, $umkm));
            $this->isiInformasiProyek($browser);
            $browser->radio('payment_method', 'sekali');
            $this->isiKondisiTermin($browser, 1, $kondisi);

            // Kolom kondisi berhenti menerima ketikan di karakter ke-255.
            $this->assertSame(255, mb_strlen($browser->inputValue('#tabel-termin tbody tr:nth-child(1) [name$="[condition]"]')));

            $this->tekan($browser, '@simpan-draf')->waitForText('Draf proyek disimpan.', 15);
        });

        $this->assertSame(mb_substr($kondisi, 0, 255), Project::sole()->paymentTerms()->sole()->condition);
    }

    /** Memilih Bertahap lalu mengisi dua termin dengan nominal yang diberikan. */
    private function isiDuaTermin(Browser $browser, string $nominal1, string $nominal2): Browser
    {
        $browser->radio('payment_method', 'bertahap');
        $this->isiNominalTermin($browser, 1, $nominal1);
        $this->isiKondisiTermin($browser, 1, 'Uang muka setelah kesepakatan berstatus aktif');
        $this->isiNominalTermin($browser, 2, $nominal2);

        return $this->isiKondisiTermin($browser, 2, 'Pelunasan setelah hasil akhir disetujui UMKM');
    }
}
