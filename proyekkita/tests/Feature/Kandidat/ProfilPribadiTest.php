<?php

namespace Tests\Feature\Kandidat;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsApplicationScenario;
use Tests\Concerns\StubsLoginRoute;
use Tests\TestCase;

/**
 * [PBI-02 / FR-03] Profil pribadi dan akademik kandidat.
 * Kode skenario (F02-xx) dipakai di laporan pengujian.
 */
class ProfilPribadiTest extends TestCase
{
    use RefreshDatabase, BuildsApplicationScenario, StubsLoginRoute;

    private function payload(array $o = []): array
    {
        return array_merge([
            'full_name' => 'Fajar Pratama',
            'city' => 'Kota Bandung',
            'headline' => 'Desainer grafis & kemasan produk UMKM',
            'university' => 'Telkom University',
            'major' => 'S1 Desain Komunikasi Visual',
            'summary' => 'Mahasiswa DKV semester 7 yang berfokus pada desain kemasan.',
        ], $o);
    }

    private function simpan(User $user, array $o = [])
    {
        return $this->actingAs($user)->put(route('kandidat.profil.datadiri.update'), $this->payload($o));
    }

    // ---------------------------------------------------------------- Halaman & akses (kriteria 3, 4, 5)

    /** F02-01 */
    public function test_kandidat_dapat_membuka_halaman_data_diri(): void
    {
        $this->actingAs($this->makeKandidat())->get(route('kandidat.profil.datadiri'))
            ->assertOk()
            ->assertSee('Data Pribadi')
            ->assertSee('Kelengkapan Profil')
            ->assertSee('Siapa yang bisa melihat?');
    }

    /** F02-02 */
    public function test_umkm_tidak_dapat_membuka_data_diri_kandidat(): void
    {
        $this->actingAs($this->makeUmkm())->get(route('kandidat.profil.datadiri'))->assertForbidden();
    }

    /** F02-03 */
    public function test_admin_tidak_dapat_membuka_maupun_mengubah_data_diri_kandidat(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('kandidat.profil.datadiri'))->assertForbidden();
        $this->actingAs($admin)->put(route('kandidat.profil.datadiri.update'), $this->payload())->assertForbidden();
        $this->assertDatabaseCount('candidate_profiles', 0);
    }

    /** F02-04 */
    public function test_umkm_tidak_dapat_mengubah_data_diri_kandidat(): void
    {
        $this->actingAs($this->makeUmkm())->put(route('kandidat.profil.datadiri.update'), $this->payload())->assertForbidden();
        $this->assertDatabaseCount('candidate_profiles', 0);
    }

    /** F02-05 */
    public function test_tamu_dialihkan_ke_halaman_login(): void
    {
        $this->stubLoginRoute();

        $this->get(route('kandidat.profil.datadiri'))->assertRedirect(route('login'));
        $this->put(route('kandidat.profil.datadiri.update'), $this->payload())->assertRedirect(route('login'));
        $this->assertDatabaseCount('candidate_profiles', 0);
    }

    /** F02-06 (kriteria 5: hanya pemilik; pengiriman user_id / id kandidat lain diabaikan) */
    public function test_kandidat_hanya_dapat_mengubah_profilnya_sendiri(): void
    {
        $a = $this->makeKandidat(['full_name' => 'Kandidat A', 'university' => 'Univ A']);
        $b = $this->makeKandidat(['full_name' => 'Kandidat B', 'university' => 'Univ B']);

        $this->simpan($a, ['university' => 'Univ A Baru', 'user_id' => $b->id, 'id' => $b->candidateProfile->id])
            ->assertRedirect(route('kandidat.profil.datadiri'));

        $this->assertDatabaseHas('candidate_profiles', ['user_id' => $a->id, 'university' => 'Univ A Baru']);
        $this->assertDatabaseHas('candidate_profiles', ['user_id' => $b->id, 'university' => 'Univ B', 'full_name' => 'Kandidat B']);
        $this->assertDatabaseCount('candidate_profiles', 2);
    }

    /** F02-07 (kriteria 3: data tersimpan lalu tampil kembali saat halaman dibuka lagi) */
    public function test_data_tersimpan_tampil_kembali_di_form(): void
    {
        $user = $this->makeKandidat();
        $this->simpan($user, [
            'full_name' => 'Nama Tersimpan', 'city' => 'Kabupaten Sleman', 'headline' => 'Headline tersimpan',
            'university' => 'Universitas Gadjah Mada', 'major' => 'S1 Ilmu Komputer', 'summary' => 'Ringkasan tersimpan.',
        ]);

        $this->actingAs($user->fresh())->get(route('kandidat.profil.datadiri'))
            ->assertOk()
            ->assertSee('value="Nama Tersimpan"', false)
            ->assertSee('value="Kabupaten Sleman"', false)
            ->assertSee('value="Headline tersimpan"', false)
            ->assertSee('value="Universitas Gadjah Mada"', false)
            ->assertSee('value="S1 Ilmu Komputer"', false)
            ->assertSee('Ringkasan tersimpan.');
    }

    /** F02-08 */
    public function test_kandidat_baru_tanpa_profil_dapat_membuka_form_kosong(): void
    {
        $this->actingAs(User::factory()->kandidat()->create())->get(route('kandidat.profil.datadiri'))
            ->assertOk()
            ->assertSee('belum lengkap');
    }

    /** F02-09 */
    public function test_tab_navigasi_profil_tersedia(): void
    {
        $this->actingAs($this->makeKandidat())->get(route('kandidat.profil.datadiri'))
            ->assertSee('Data Diri')
            ->assertSee('Keterampilan &amp; Pengalaman', false)
            ->assertSee('Portofolio')
            ->assertSee(route('kandidat.profil.edit'), false)
            ->assertSee(route('kandidat.profil.portofolio'), false);
    }

    // ---------------------------------------------------------------- Simpan (kriteria 1, 3)

    /** F02-10 */
    public function test_kandidat_baru_tanpa_profil_dapat_membuat_profil(): void
    {
        $user = User::factory()->kandidat()->create();

        $this->simpan($user)
            ->assertRedirect(route('kandidat.profil.datadiri'))
            ->assertSessionHas('success', 'Data diri berhasil disimpan.');

        $this->assertDatabaseHas('candidate_profiles', [
            'user_id' => $user->id,
            'full_name' => 'Fajar Pratama',
            'city' => 'Kota Bandung',
            'headline' => 'Desainer grafis & kemasan produk UMKM',
            'university' => 'Telkom University',
            'major' => 'S1 Desain Komunikasi Visual',
        ]);
    }

    /** F02-11 */
    public function test_kandidat_dapat_memperbarui_profil_tanpa_membuat_baris_ganda(): void
    {
        $user = $this->makeKandidat();

        $this->simpan($user, ['university' => 'ITB']);
        $this->simpan($user->fresh(), [
            'university' => 'ITB',
            'major' => 'S1 Teknik Informatika',
        ]);

        $this->assertDatabaseHas('candidate_profiles', ['user_id' => $user->id, 'university' => 'ITB', 'major' => 'S1 Teknik Informatika']);
        $this->assertDatabaseCount('candidate_profiles', 1);
    }

    /** F02-12 (regresi PBI-03/04: keterampilan dan portofolio tidak terhapus saat data diri disimpan) */
    public function test_menyimpan_data_diri_tidak_menghapus_keterampilan_pengalaman_portofolio(): void
    {
        $user = $this->makeKandidat([], skills: 2, experiences: 1, portfolios: 1);

        $this->simpan($user);

        $profile = $user->candidateProfile()->first();
        $this->assertSame(2, $profile->skills()->count());
        $this->assertSame(1, $profile->experiences()->count());
        $this->assertSame(1, $profile->portfolios()->count());
    }

    /** F02-13 */
    public function test_nama_di_layout_mengikuti_nama_lengkap_terbaru(): void
    {
        $user = $this->makeKandidat();
        $this->simpan($user, ['full_name' => 'Budi Santoso Pratama']);

        $this->actingAs($user->fresh())->get(route('kandidat.profil.datadiri'))->assertSee('Budi Santoso Pratama');
    }

    /** F02-14 (spasi di awal/akhir dibuang, simbol & HTML tidak dieksekusi) */
    public function test_input_dipangkas_dan_html_dikeluarkan_dengan_aman(): void
    {
        $user = $this->makeKandidat();
        $this->simpan($user, ['full_name' => '   Siti Aminah   ', 'summary' => '<script>alert(1)</script>']);

        $this->assertDatabaseHas('candidate_profiles', ['user_id' => $user->id, 'full_name' => 'Siti Aminah']);

        $this->actingAs($user->fresh())->get(route('kandidat.profil.datadiri'))
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    // ---------------------------------------------------------------- Validasi (kriteria 1, 3)

    /** F02-15 */
    public function test_semua_isian_wajib(): void
    {
        $this->actingAs($this->makeKandidat())->put(route('kandidat.profil.datadiri.update'), [])
            ->assertSessionHasErrors([
                'full_name' => 'Nama lengkap wajib diisi.',
                'city' => 'Domisili wajib diisi.',
                'headline' => 'Headline wajib diisi.',
                'university' => 'Universitas wajib diisi.',
                'major' => 'Program studi wajib diisi.',
                'summary' => 'Ringkasan diri wajib diisi.',
            ]);
    }

    /** F02-16 (negative: hanya spasi dianggap kosong) */
    public function test_isian_berisi_spasi_saja_dianggap_kosong(): void
    {
        $user = $this->makeKandidat();

        $this->simpan($user, ['full_name' => '   ', 'summary' => "  \n "])
            ->assertSessionHasErrors(['full_name', 'summary']);
    }

    /** F02-17 (boundary headline: 80 diterima, 81 ditolak, kosong ditolak) */
    public function test_batas_headline_80_karakter(): void
    {
        $user = $this->makeKandidat();

        $this->simpan($user, ['headline' => str_repeat('a', 81)])
            ->assertSessionHasErrors(['headline' => 'Headline maksimal 80 karakter.']);

        $this->simpan($user, ['headline' => str_repeat('a', 80)])
            ->assertSessionHasNoErrors();
        $this->assertSame(80, mb_strlen($user->candidateProfile()->first()->headline));
    }

    /** F02-18 (boundary panjang kolom lain) */
    public function test_batas_panjang_isian_lain(): void
    {
        $user = $this->makeKandidat();

        $this->simpan($user, [
            'full_name' => str_repeat('a', 101), 'city' => str_repeat('a', 101), 'university' => str_repeat('a', 151),
            'major' => str_repeat('a', 101), 'summary' => str_repeat('a', 1001),
        ])->assertSessionHasErrors([
            'full_name' => 'Nama lengkap maksimal 100 karakter.',
            'city' => 'Domisili maksimal 100 karakter.',
            'university' => 'Universitas maksimal 150 karakter.',
            'major' => 'Program studi maksimal 100 karakter.',
            'summary' => 'Ringkasan diri maksimal 1000 karakter.',
        ]);

        $this->simpan($user, [
            'full_name' => str_repeat('a', 100), 'city' => str_repeat('a', 100), 'university' => str_repeat('a', 150),
            'major' => str_repeat('a', 100), 'summary' => str_repeat('a', 1000),
        ])->assertSessionHasNoErrors();
    }

    /** F02-19 */
    public function test_kegagalan_validasi_tidak_mengubah_data_dan_mengembalikan_isian(): void
    {
        $user = $this->makeKandidat(['full_name' => 'Nama Lama']);

        $this->actingAs($user)->from(route('kandidat.profil.datadiri'))
            ->put(route('kandidat.profil.datadiri.update'), $this->payload(['full_name' => 'Nama Baru', 'headline' => str_repeat('a', 81)]))
            ->assertRedirect(route('kandidat.profil.datadiri'))
            ->assertSessionHasInput('full_name', 'Nama Baru');

        $this->assertDatabaseHas('candidate_profiles', ['user_id' => $user->id, 'full_name' => 'Nama Lama']);
    }

    // ---------------------------------------------------------------- Foto profil (kriteria 2)

    /** F02-20 */
    public function test_foto_valid_tersimpan_dan_foto_lama_diganti(): void
    {
        Storage::fake('public');
        $user = $this->makeKandidat();

        $this->simpan($user, ['photo' => UploadedFile::fake()->image('a.jpg')]);
        $first = $user->candidateProfile()->first()->photo;
        Storage::disk('public')->assertExists($first);

        $this->simpan($user->fresh(), ['photo' => UploadedFile::fake()->image('b.png')]);
        $second = $user->candidateProfile()->first()->photo;

        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertExists($second);
        Storage::disk('public')->assertMissing($first);
    }

    /** F02-21 */
    public function test_foto_tampil_di_halaman_setelah_diunggah(): void
    {
        Storage::fake('public');
        $user = $this->makeKandidat();
        $this->simpan($user, ['photo' => UploadedFile::fake()->image('a.jpg')]);

        $path = $user->candidateProfile()->first()->photo;

        $this->actingAs($user->fresh())->get(route('kandidat.profil.datadiri'))
            ->assertSee('dusk="foto-tampil"', false)
            ->assertSee('storage/'.$path, false);
    }

    /** F02-22 (boundary: tepat 2 MB diterima, 2 MB + 1 KB ditolak) */
    public function test_batas_ukuran_foto_2mb(): void
    {
        Storage::fake('public');
        $user = $this->makeKandidat();

        $this->simpan($user, ['photo' => UploadedFile::fake()->image('besar.jpg')->size(2049)])
            ->assertSessionHasErrors(['photo' => 'Ukuran foto profil maksimal 2 MB.']);
        $this->assertNull($user->candidateProfile()->first()->photo);

        $this->simpan($user, ['photo' => UploadedFile::fake()->image('pas.jpg')->size(2048)])
            ->assertSessionHasNoErrors();
        $this->assertNotNull($user->candidateProfile()->first()->photo);
    }

    /** F02-23 */
    public function test_foto_lebih_dari_2mb_ditolak(): void
    {
        Storage::fake('public');

        $this->simpan($this->makeKandidat(), ['photo' => UploadedFile::fake()->image('besar.jpg')->size(3000)])
            ->assertSessionHasErrors('photo');
    }

    /** F02-24 (negative: dokumen, GIF, SVG, dan teks berekstensi gambar) */
    public function test_foto_dengan_format_tidak_diizinkan_ditolak(): void
    {
        Storage::fake('public');
        $user = $this->makeKandidat();

        $berkas = [
            'pdf' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'),
            'gif' => UploadedFile::fake()->image('anim.gif'),
            'svg' => UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>'),
            'palsu' => UploadedFile::fake()->createWithContent('palsu.jpg', 'ini bukan gambar'),
        ];

        foreach ($berkas as $nama => $file) {
            $this->simpan($user, ['photo' => $file])->assertSessionHasErrors('photo');
        }

        $this->assertNull($user->candidateProfile()->first()->photo);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    /** F02-25 */
    public function test_foto_bukan_gambar_ditolak(): void
    {
        Storage::fake('public');

        $this->simpan($this->makeKandidat(), ['photo' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf')])
            ->assertSessionHasErrors('photo');
    }

    /** F02-26 (kolom foto berisi teks, bukan berkas, tidak boleh lolos) */
    public function test_foto_berupa_teks_ditolak(): void
    {
        $user = $this->makeKandidat();

        $this->simpan($user, ['photo' => '../../etc/passwd'])->assertSessionHasErrors('photo');
        $this->assertNull($user->candidateProfile()->first()->photo);
    }

    /** F02-27 (foto bersifat opsional dan tidak terhapus bila tidak diganti) */
    public function test_menyimpan_tanpa_foto_mempertahankan_foto_lama(): void
    {
        Storage::fake('public');
        $user = $this->makeKandidat();

        $this->simpan($user, ['photo' => UploadedFile::fake()->image('a.jpg')]);
        $foto = $user->candidateProfile()->first()->photo;

        $this->simpan($user->fresh(), ['headline' => 'Headline baru'])->assertSessionHasNoErrors();

        $this->assertSame($foto, $user->candidateProfile()->first()->photo);
        Storage::disk('public')->assertExists($foto);
    }

    /** F02-28 (foto lama tidak hilang bila penggantian foto gagal validasi) */
    public function test_foto_lama_tetap_ada_saat_unggahan_baru_gagal_validasi(): void
    {
        Storage::fake('public');
        $user = $this->makeKandidat();

        $this->simpan($user, ['photo' => UploadedFile::fake()->image('a.jpg')]);
        $foto = $user->candidateProfile()->first()->photo;

        $this->simpan($user->fresh(), ['photo' => UploadedFile::fake()->create('cv.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors('photo');

        $this->assertSame($foto, $user->candidateProfile()->first()->photo);
        Storage::disk('public')->assertExists($foto);
    }

    // ---------------------------------------------------------------- Panel kanan (kriteria 4)

    /** F02-29 */
    public function test_kelengkapan_profil_naik_setelah_data_diri_dan_foto_disimpan(): void
    {
        Storage::fake('public');
        $user = User::factory()->kandidat()->create();

        $this->actingAs($user)->get(route('kandidat.profil.datadiri'))
            ->assertSee('id="completeness-pct">0%', false);

        $this->simpan($user);
        $this->actingAs($user->fresh())->get(route('kandidat.profil.datadiri'))
            ->assertSee('id="completeness-pct">25%', false);

        $this->simpan($user->fresh(), ['photo' => UploadedFile::fake()->image('a.jpg')]);
        $this->actingAs($user->fresh())->get(route('kandidat.profil.datadiri'))
            ->assertSee('id="completeness-pct">40%', false);
    }
}
