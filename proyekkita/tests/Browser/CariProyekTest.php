<?php

namespace Tests\Feature\Kandidat;

use App\Enums\ProjectStatus;
use App\Models\Category;
use App\Models\Skill;
use App\Models\User;
use App\Services\ProjectDiscoveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\BuildsProjectCatalogScenario;
use Tests\TestCase;

/**
 * [PBI-15 / FR-08] Pencarian kata kunci, filter (kategori, keterampilan, kompensasi, tenggat), dan pengurutan
 * pada halaman Eksplor Proyek.
 *
 * Katalog uji (siapkanKatalog):
 *   Logo    : Desain, Figma,         Rp1.500.000, tenggat +20 hari, terbit 3 hari lalu
 *   Rekap   : Data,   Excel,         Rp  800.000, tenggat  +5 hari, terbit 2 hari lalu
 *   Katalog : Desain, Figma + Excel, Rp3.000.000, tenggat +12 hari, terbit 1 hari lalu
 */
class CariProyekTest extends TestCase
{
    use BuildsProjectCatalogScenario;
    use RefreshDatabase;

    private const LOGO = 'Desain Logo Kedai Kopi';
    private const REKAP = 'Rekap Penjualan Bulanan';
    private const KATALOG = 'Katalog Produk Digital';

    private ?User $kandidatUji = null;
    private Category $desain;
    private Category $data;
    private Skill $figma;
    private Skill $excel;

    // ---------- Akses ----------

    public function test_tamu_diarahkan_ke_halaman_login(): void
    {
        $this->get(route('kandidat.proyek.index', ['cari' => 'logo']))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_pencarian_hanya_untuk_kandidat(): void
    {
        $this->actingAs($this->makeUmkm())->get(route('kandidat.proyek.index', ['cari' => 'logo']))->assertForbidden();
    }

    // ---------- Validasi parameter ----------

    public static function parameterTidakValid(): array
    {
        return [
            'urutan tidak dikenal' => [['urut' => 'ngawur'], 'urut'],
            'kategori tidak ada' => [['kategori' => 999999], 'kategori'],
            'keterampilan tidak ada' => [['keterampilan' => [999999]], 'keterampilan.0'],
            'kompensasi minimum negatif' => [['kompensasi_min' => -1], 'kompensasi_min'],
            'kompensasi minimum bukan angka' => [['kompensasi_min' => 'abc'], 'kompensasi_min'],
            'kompensasi maksimum lebih kecil dari minimum' => [['kompensasi_min' => 5000000, 'kompensasi_maks' => 1000000], 'kompensasi_maks'],
            'tanggal tenggat tidak valid' => [['tenggat_sebelum' => 'bukan-tanggal'], 'tenggat_sebelum'],
            'kata kunci terlalu panjang' => [['cari' => str_repeat('a', 101)], 'cari'],
        ];
    }

    #[DataProvider('parameterTidakValid')]
    public function test_parameter_tidak_valid_dikembalikan_ke_daftar_dengan_galat(array $query, string $kunciGalat): void
    {
        $this->siapkanKatalog();

        $this->actingAs($this->kandidat())
            ->get(route('kandidat.proyek.index', $query))
            ->assertRedirect(route('kandidat.proyek.index'))
            ->assertSessionHasErrors($kunciGalat);
    }

    public function test_pesan_galat_kompensasi_maksimum_berbahasa_indonesia(): void
    {
        $this->actingAs($this->kandidat())
            ->get(route('kandidat.proyek.index', ['kompensasi_min' => 5000000, 'kompensasi_maks' => 1000000]))
            ->assertSessionHasErrors(['kompensasi_maks' => 'Kompensasi maksimum tidak boleh lebih kecil dari kompensasi minimum.']);
    }

    public function test_galat_validasi_tampil_di_halaman_daftar(): void
    {
        $this->siapkanKatalog();

        $this->actingAs($this->kandidat())
            ->followingRedirects()
            ->get(route('kandidat.proyek.index', ['kompensasi_min' => 5000000, 'kompensasi_maks' => 1000000]))
            ->assertOk()
            ->assertSee('Filter belum dapat diterapkan')
            ->assertSee('Kompensasi maksimum tidak boleh lebih kecil dari kompensasi minimum.')
            ->assertSee(self::LOGO);
    }

    public function test_kompensasi_maksimum_tanpa_minimum_dan_minimum_sama_dengan_maksimum_valid(): void
    {
        $this->siapkanKatalog();

        $this->actingAs($this->kandidat())->get(route('kandidat.proyek.index', ['kompensasi_maks' => 1000000]))->assertOk();
        $this->get(route('kandidat.proyek.index', ['kompensasi_min' => 800000, 'kompensasi_maks' => 800000]))->assertOk();
    }

    public function test_parameter_kosong_dianggap_tidak_ada_filter(): void
    {
        $this->siapkanKatalog();

        $hasil = $this->judulHasil(['cari' => '', 'kategori' => '', 'kompensasi_min' => '', 'kompensasi_maks' => '', 'tenggat_sebelum' => '', 'urut' => '']);

        $this->assertEqualsCanonicalizing([self::LOGO, self::REKAP, self::KATALOG], $hasil);
    }

    public function test_urutan_bawaan_dipakai_jika_parameter_urut_tidak_dikirim(): void
    {
        $this->siapkanKatalog();

        $filters = $this->actingAs($this->kandidat())->get(route('kandidat.proyek.index'))->viewData('filters');

        $this->assertSame('terbaru', $filters['urut']);
        $this->assertSame('', $filters['cari']);
        $this->assertNull($filters['kategori']);
        $this->assertSame([], $filters['keterampilan']);
    }

    // ---------- Kata kunci ----------

    public function test_kata_kunci_mencari_pada_judul(): void
    {
        $this->siapkanKatalog();

        $this->assertSame([self::LOGO], $this->judulHasil(['cari' => 'logo']));
    }

    public function test_kata_kunci_mencari_pada_deskripsi(): void
    {
        $this->siapkanKatalog();

        // "spreadsheet" hanya ada di deskripsi proyek Rekap.
        $this->assertSame([self::REKAP], $this->judulHasil(['cari' => 'spreadsheet']));
    }

    public function test_kata_kunci_tidak_membedakan_huruf_besar_dan_kecil(): void
    {
        $this->siapkanKatalog();

        $this->assertSame([self::LOGO], $this->judulHasil(['cari' => 'LOGO']));
        $this->assertSame([self::REKAP], $this->judulHasil(['cari' => 'SpReadSheet']));
    }

    public function test_spasi_di_tepi_kata_kunci_diabaikan(): void
    {
        $this->siapkanKatalog();

        $this->assertSame([self::LOGO], $this->judulHasil(['cari' => '   logo   ']));
    }

    public function test_kata_kunci_tanpa_hasil_menampilkan_pesan_tidak_cocok(): void
    {
        $this->siapkanKatalog();

        $response = $this->actingAs($this->kandidat())
            ->get(route('kandidat.proyek.index', ['cari' => 'zzxxqq']))
            ->assertOk()
            ->assertSee('Tidak ada proyek yang cocok')
            ->assertSee('0 proyek ditemukan')
            ->assertDontSee('Belum ada proyek yang terbuka');

        $this->assertSame(0, $response->viewData('projects')->total());
    }

    public function test_karakter_khusus_like_dicari_apa_adanya(): void
    {
        $this->makeCatalogProject('Diskon 100% Organik');
        $this->makeCatalogProject('Kopi 100 Gram');
        $this->makeCatalogProject('Kode A_B');
        $this->makeCatalogProject('Kode AxB');
        $this->makeCatalogProject('Promo Hebat!');
        $this->makeCatalogProject('Promo Hebat');

        $this->assertSame(['Diskon 100% Organik'], $this->judulHasil(['cari' => '100%']));
        $this->assertSame(['Kode A_B'], $this->judulHasil(['cari' => 'A_B']));
        $this->assertSame(['Promo Hebat!'], $this->judulHasil(['cari' => 'Hebat!']));
    }

    public function test_kata_kunci_hanya_mencocokkan_judul_dan_deskripsi_bukan_kolom_lain(): void
    {
        // expected_output memuat kata "Figma", tetapi judul dan deskripsi tidak.
        $this->makeCatalogProject('Proyek Tanpa Kata', ['description' => 'Deskripsi biasa.', 'expected_output' => 'Menyerahkan file Figma']);

        $this->assertSame([], $this->judulHasil(['cari' => 'Figma']));
    }

    // ---------- Filter ----------

    public function test_filter_kategori(): void
    {
        $this->siapkanKatalog();

        $this->assertEqualsCanonicalizing([self::LOGO, self::KATALOG], $this->judulHasil(['kategori' => $this->desain->id]));
        $this->assertSame([self::REKAP], $this->judulHasil(['kategori' => $this->data->id]));
    }

    public function test_filter_satu_keterampilan(): void
    {
        $this->siapkanKatalog();

        $this->assertEqualsCanonicalizing([self::REKAP, self::KATALOG], $this->judulHasil(['keterampilan' => [$this->excel->id]]));
        $this->assertEqualsCanonicalizing([self::LOGO, self::KATALOG], $this->judulHasil(['keterampilan' => [$this->figma->id]]));
    }

    public function test_beberapa_keterampilan_menampilkan_proyek_yang_membutuhkan_salah_satunya_tanpa_duplikat(): void
    {
        $this->siapkanKatalog();

        // Katalog membutuhkan Figma dan Excel sekaligus, tetapi hanya muncul satu kali.
        $response = $this->actingAs($this->kandidat())
            ->get(route('kandidat.proyek.index', ['keterampilan' => [$this->figma->id, $this->excel->id]]))
            ->assertOk();

        $judul = $response->viewData('projects')->pluck('title')->all();
        $this->assertEqualsCanonicalizing([self::LOGO, self::REKAP, self::KATALOG], $judul);
        $this->assertSame(3, $response->viewData('projects')->total());
    }

    public function test_filter_kompensasi_minimum(): void
    {
        $this->siapkanKatalog();

        $this->assertEqualsCanonicalizing([self::LOGO, self::KATALOG], $this->judulHasil(['kompensasi_min' => 1000000]));
    }

    public function test_filter_kompensasi_maksimum(): void
    {
        $this->siapkanKatalog();

        $this->assertSame([self::REKAP], $this->judulHasil(['kompensasi_maks' => 1000000]));
    }

    public function test_filter_rentang_kompensasi(): void
    {
        $this->siapkanKatalog();

        $this->assertSame([self::LOGO], $this->judulHasil(['kompensasi_min' => 1000000, 'kompensasi_maks' => 2000000]));
    }

    public function test_batas_kompensasi_bersifat_inklusif(): void
    {
        $this->siapkanKatalog();

        // Tepat Rp800.000 (Rekap) dan tepat Rp3.000.000 (Katalog) ikut tampil.
        $this->assertSame([self::REKAP], $this->judulHasil(['kompensasi_min' => 800000, 'kompensasi_maks' => 800000]));
        $this->assertSame([self::KATALOG], $this->judulHasil(['kompensasi_min' => 3000000]));
    }

    public function test_filter_tenggat_lamaran_paling_lambat_bersifat_inklusif(): void
    {
        $this->siapkanKatalog();

        // Rekap berakhir +5 hari: ikut tampil pada tanggal +5, tidak ikut pada +4.
        $this->assertSame([self::REKAP], $this->judulHasil(['tenggat_sebelum' => now()->addDays(5)->toDateString()]));
        $this->assertSame([], $this->judulHasil(['tenggat_sebelum' => now()->addDays(4)->toDateString()]));
        $this->assertEqualsCanonicalizing([self::REKAP, self::KATALOG], $this->judulHasil(['tenggat_sebelum' => now()->addDays(12)->toDateString()]));
    }

    public function test_beberapa_filter_digabung_dengan_and(): void
    {
        $this->siapkanKatalog();

        $this->assertSame([self::LOGO], $this->judulHasil([
            'cari' => 'kedai',
            'kategori' => $this->desain->id,
            'keterampilan' => [$this->figma->id],
            'kompensasi_maks' => 2000000,
        ]));

        // Kategori Desain tetapi kompensasi maksimum Rp1.000.000: tidak ada yang memenuhi.
        $this->assertSame([], $this->judulHasil(['kategori' => $this->desain->id, 'kompensasi_maks' => 1000000]));
    }

    public function test_filter_hanya_berlaku_pada_proyek_yang_masih_terbuka(): void
    {
        $this->makeCatalogProject('Poster Terbuka');
        $this->makeCatalogProject('Poster Draf', ['status' => ProjectStatus::Draft]);
        $this->makeCatalogProject('Poster Menunggu', ['status' => ProjectStatus::PendingReview]);
        $this->makeCatalogProject('Poster Ditutup', ['status' => ProjectStatus::Closed]);
        $this->makeCatalogProject('Poster Kedaluwarsa', ['application_deadline' => now()->subDay()->toDateString()]);

        $this->assertSame(['Poster Terbuka'], $this->judulHasil(['cari' => 'Poster']));
    }

    // ---------- Urutan ----------

    public function test_urutan_bawaan_dan_terbaru_memakai_waktu_terbit(): void
    {
        $this->siapkanKatalog();

        $this->assertSame([self::KATALOG, self::REKAP, self::LOGO], $this->judulHasil());
        $this->assertSame([self::KATALOG, self::REKAP, self::LOGO], $this->judulHasil(['urut' => 'terbaru']));
    }

    public function test_urut_berdasarkan_tenggat_terdekat(): void
    {
        $this->siapkanKatalog();

        $this->assertSame([self::REKAP, self::KATALOG, self::LOGO], $this->judulHasil(['urut' => 'tenggat_terdekat']));
    }

    public function test_urut_berdasarkan_kompensasi_tertinggi(): void
    {
        $this->siapkanKatalog();

        $this->assertSame([self::KATALOG, self::LOGO, self::REKAP], $this->judulHasil(['urut' => 'kompensasi_tertinggi']));
    }

    public function test_urut_berdasarkan_kompensasi_terendah(): void
    {
        $this->siapkanKatalog();

        $this->assertSame([self::REKAP, self::LOGO, self::KATALOG], $this->judulHasil(['urut' => 'kompensasi_terendah']));
    }

    public function test_nilai_yang_sama_diurutkan_dengan_proyek_terbaru_dibuat_lebih_dulu(): void
    {
        $pertama = $this->makeCatalogProject('Kembar Pertama', ['total_compensation' => 1000000]);
        $kedua = $this->makeCatalogProject('Kembar Kedua', ['total_compensation' => 1000000, 'published_at' => $pertama->published_at]);

        $this->assertSame(['Kembar Kedua', 'Kembar Pertama'], $this->judulHasil(['urut' => 'kompensasi_tertinggi']));
        $this->assertSame(['Kembar Kedua', 'Kembar Pertama'], $this->judulHasil(['urut' => 'kompensasi_terendah']));
        $this->assertGreaterThan($pertama->id, $kedua->id);
    }

    public function test_urutan_dapat_digabung_dengan_filter(): void
    {
        $this->siapkanKatalog();

        $this->assertSame(
            [self::KATALOG, self::LOGO],
            $this->judulHasil(['kategori' => $this->desain->id, 'urut' => 'kompensasi_tertinggi']),
        );
    }

    // ---------- Tampilan form dan hasil ----------

    public function test_form_mempertahankan_pilihan_yang_sedang_dipakai(): void
    {
        $this->siapkanKatalog();

        $html = $this->actingAs($this->kandidat())->get(route('kandidat.proyek.index', [
            'cari' => 'kedai',
            'kategori' => $this->desain->id,
            'keterampilan' => [$this->figma->id],
            'kompensasi_min' => 1000000,
            'kompensasi_maks' => 2000000,
            'tenggat_sebelum' => '2099-12-31',
            'urut' => 'tenggat_terdekat',
        ]))->assertOk()->getContent();

        $this->assertStringContainsString('name="cari" maxlength="100" value="kedai"', $html);
        $this->assertStringContainsString('value="'.$this->desain->id.'" selected', $html);
        $this->assertStringContainsString('value="tenggat_terdekat" selected', $html);
        $this->assertStringContainsString('name="kompensasi_min" value="1000000"', $html);
        $this->assertStringContainsString('name="kompensasi_maks" value="2000000"', $html);
        $this->assertStringContainsString('name="tenggat_sebelum" value="2099-12-31"', $html);
        $this->assertMatchesRegularExpression('/dusk="filter-keterampilan-'.$this->figma->id.'"\s+checked/', $html);
        $this->assertDoesNotMatchRegularExpression('/dusk="filter-keterampilan-'.$this->excel->id.'"\s+checked/', $html);
    }

    public function test_jumlah_hasil_dan_jumlah_filter_aktif_ditampilkan(): void
    {
        $this->siapkanKatalog();

        $this->actingAs($this->kandidat())
            ->get(route('kandidat.proyek.index', ['kategori' => $this->desain->id, 'kompensasi_maks' => 2000000]))
            ->assertSee('1 proyek ditemukan')
            ->assertSee('2 filter aktif');

        $this->get(route('kandidat.proyek.index'))
            ->assertSee('3 proyek ditemukan')
            ->assertDontSee('filter aktif');
    }

    public function test_urutan_saja_tidak_dihitung_sebagai_filter_aktif(): void
    {
        $this->siapkanKatalog();

        $this->actingAs($this->kandidat())
            ->get(route('kandidat.proyek.index', ['urut' => 'kompensasi_tertinggi']))
            ->assertDontSee('filter aktif');
    }

    public function test_tautan_reset_mengarah_ke_daftar_tanpa_parameter(): void
    {
        $this->siapkanKatalog();

        $html = $this->actingAs($this->kandidat())->get(route('kandidat.proyek.index', ['cari' => 'logo']))->getContent();

        $this->assertMatchesRegularExpression('/href="'.preg_quote(route('kandidat.proyek.index'), '/').'"[^>]*dusk="reset-filter"/', $html);
    }

    public function test_pilihan_filter_hanya_memuat_kategori_dan_keterampilan_aktif(): void
    {
        $this->siapkanKatalog();
        (new Category())->forceFill(['name' => 'Kategori Nonaktif', 'is_active' => false])->save();
        (new Skill())->forceFill(['name' => 'Keterampilan Nonaktif', 'is_active' => false])->save();

        $response = $this->actingAs($this->kandidat())->get(route('kandidat.proyek.index'))->assertOk();

        $this->assertContains('Desain Grafis & Branding', $response->viewData('categories')->pluck('name')->all());
        $this->assertNotContains('Kategori Nonaktif', $response->viewData('categories')->pluck('name')->all());
        $this->assertContains('Figma', $response->viewData('skills')->pluck('name')->all());
        $this->assertNotContains('Keterampilan Nonaktif', $response->viewData('skills')->pluck('name')->all());
    }

    public function test_proyek_dengan_kategori_nonaktif_tetap_tampil_di_daftar(): void
    {
        $nonaktif = (new Category());
        $nonaktif->forceFill(['name' => 'Kategori Lama', 'is_active' => false])->save();
        $this->makeCatalogProject('Proyek Kategori Lama', [], $nonaktif);

        $this->assertSame(['Proyek Kategori Lama'], $this->judulHasil());
    }

    // ---------- Pagination ----------

    public function test_pindah_halaman_membawa_kata_kunci_dan_filter(): void
    {
        for ($i = 1; $i <= 11; $i++) {
            $this->makeCatalogProject("Poster Promosi {$i}", ['total_compensation' => 500000]);
        }
        $this->makeCatalogProject('Laporan Keuangan');

        $halaman1 = $this->actingAs($this->kandidat())
            ->get(route('kandidat.proyek.index', ['cari' => 'Poster', 'kompensasi_maks' => 600000, 'urut' => 'kompensasi_terendah']))
            ->assertOk();

        $paginator = $halaman1->viewData('projects');
        $this->assertSame(11, $paginator->total());
        $this->assertCount(10, $paginator->items());

        $next = $paginator->nextPageUrl();
        $this->assertStringContainsString('cari=Poster', $next);
        $this->assertStringContainsString('kompensasi_maks=600000', $next);
        $this->assertStringContainsString('urut=kompensasi_terendah', $next);

        $halaman2 = $this->get($next)->assertOk();
        $this->assertCount(1, $halaman2->viewData('projects')->items());
        $halaman2->assertDontSee('Laporan Keuangan');
    }

    // ---------- Service ----------

    public function test_service_menghitung_filter_aktif_tanpa_menghitung_urutan_atau_nilai_kosong(): void
    {
        $service = app(ProjectDiscoveryService::class);

        $this->assertSame(0, $service->activeFilterCount([]));
        $this->assertSame(0, $service->activeFilterCount(['cari' => '', 'kategori' => null, 'keterampilan' => [], 'kompensasi_min' => '', 'urut' => 'kompensasi_tertinggi']));
        $this->assertSame(1, $service->activeFilterCount(['cari' => 'logo', 'urut' => 'terbaru']));
        $this->assertSame(6, $service->activeFilterCount([
            'cari' => 'a', 'kategori' => 1, 'keterampilan' => [1], 'kompensasi_min' => '0', 'kompensasi_maks' => '10', 'tenggat_sebelum' => '2099-01-01',
        ]));
    }

    public function test_service_listopen_berjalan_tanpa_filter_dan_dengan_kunci_yang_tidak_lengkap(): void
    {
        $this->siapkanKatalog();
        $service = app(ProjectDiscoveryService::class);

        $this->assertSame(3, $service->listOpen()->total());
        $this->assertSame(3, $service->listOpen([])->total());
        $this->assertSame(1, $service->listOpen(['cari' => 'logo'])->total());
        $this->assertSame(2, $service->listOpen(['urut' => 'terbaru'], 2)->count());
    }

    // ---------- Pembantu ----------

    private function kandidat(): User
    {
        return $this->kandidatUji ??= $this->makeKandidat();
    }

    /** Judul proyek pada hasil pencarian, sesuai urutan tampil. */
    private function judulHasil(array $query = []): array
    {
        return $this->actingAs($this->kandidat())
            ->get(route('kandidat.proyek.index', $query))
            ->assertOk()
            ->viewData('projects')->pluck('title')->all();
    }

    /** Tiga proyek dengan kategori, keterampilan, kompensasi, tenggat, dan waktu terbit yang berbeda. */
    private function siapkanKatalog(): void
    {
        $this->desain = $this->makeCategory('Desain Grafis & Branding');
        $this->data = $this->makeCategory('Data & Administrasi');
        $this->figma = $this->makeSkill('Figma');
        $this->excel = $this->makeSkill('Microsoft Excel');

        $this->makeCatalogProject(self::LOGO, [
            'description' => 'Membuat logo baru untuk kedai kopi di Bandung.',
            'total_compensation' => 1500000,
            'application_deadline' => now()->addDays(20)->toDateString(),
            'published_at' => now()->subDays(3),
        ], $this->desain, [$this->figma]);

        $this->makeCatalogProject(self::REKAP, [
            'description' => 'Merapikan data penjualan ke dalam spreadsheet.',
            'total_compensation' => 800000,
            'application_deadline' => now()->addDays(5)->toDateString(),
            'published_at' => now()->subDays(2),
        ], $this->data, [$this->excel]);

        $this->makeCatalogProject(self::KATALOG, [
            'description' => 'Membuat katalog dengan tampilan menarik.',
            'total_compensation' => 3000000,
            'application_deadline' => now()->addDays(12)->toDateString(),
            'published_at' => now()->subDay(),
        ], $this->desain, [$this->figma, $this->excel]);
    }
}
