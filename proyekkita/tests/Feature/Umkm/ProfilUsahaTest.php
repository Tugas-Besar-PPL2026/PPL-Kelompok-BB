<?php

namespace Tests\Feature\Umkm;

use App\Enums\VerificationStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\BuildsUmkmProfileScenario;
use Tests\Concerns\StubsLoginRoute;
use Tests\Concerns\UsesInMemoryDatabase;
use Tests\TestCase;

/**
 * [PBI-01 / FR-02] UMKM mengelola profil usaha dan melihat status verifikasi serta rating.
 * Mencakup uji positif, negatif, batas, otorisasi, dan regresi.
 */
class ProfilUsahaTest extends TestCase
{
    use BuildsUmkmProfileScenario;
    use RefreshDatabase, UsesInMemoryDatabase;
    use StubsLoginRoute;

    private function umkm(VerificationStatus $status = VerificationStatus::Verified, array $profile = []): User
    {
        return $this->makeUmkm(array_merge($this->profilePayload(), [
            'verification_status' => $status,
            'verified_at' => $status === VerificationStatus::Verified ? now() : null,
        ], $profile));
    }

    private function simpan(User $user, array $override = [])
    {
        return $this->actingAs($user)->put(route('umkm.profil.update'), $this->profilePayload($override));
    }

    private function profil(User $user)
    {
        return $user->umkmProfile()->first();
    }

    /** Badge status pada kartu "Status Verifikasi" (bukan teks peringatan yang selalu tampil). */
    private function assertBadgeStatus($response, string $label): void
    {
        $response->assertSee('dusk="status-verifikasi">'.$label.'</span>', false);
    }

    // =====================================================================
    // Positif
    // =====================================================================

    public function test_umkm_dapat_membuka_halaman_profil_dengan_data_dari_database(): void
    {
        $umkm = $this->umkm(profile: ['business_name' => 'Roastery Dari Database', 'city' => 'Cimahi']);

        $response = $this->actingAs($umkm)->get(route('umkm.profil.edit'));

        $response->assertOk()
            ->assertSee('Profil Usaha')
            ->assertSee('value="Roastery Dari Database"', false)
            ->assertSee('value="Cimahi"', false)
            ->assertSee('value="9120304567812"', false);
        $this->assertBadgeStatus($response, 'Terverifikasi');
    }

    public function test_umkm_dapat_menyimpan_profil_valid(): void
    {
        $umkm = $this->umkm();

        $this->simpan($umkm, ['city' => 'Cimahi', 'contact_phone' => '+62 812-3456-7890'])
            ->assertRedirect(route('umkm.profil.edit'))
            ->assertSessionHas('success', 'Profil usaha berhasil disimpan.');

        $this->assertDatabaseHas('umkm_profiles', [
            'user_id' => $umkm->id, 'city' => 'Cimahi', 'contact_phone' => '+62 812-3456-7890',
        ]);
    }

    public function test_umkm_baru_tanpa_profil_dapat_membuat_profil_berstatus_menunggu(): void
    {
        $user = User::factory()->umkm()->create();

        $halaman = $this->actingAs($user)->get(route('umkm.profil.edit'));
        $halaman->assertOk();
        $this->assertBadgeStatus($halaman, 'Belum diisi');

        $this->simpan($user)->assertRedirect(route('umkm.profil.edit'))->assertSessionHas('success');

        $profile = $this->profil($user);
        $this->assertNotNull($profile);
        $this->assertSame(VerificationStatus::Pending, $profile->verification_status);
        $this->assertSame('Kopi Nusantara', $profile->business_name);
    }

    public function test_cukup_nib_tanpa_tautan_dapat_disimpan(): void
    {
        $umkm = $this->umkm();

        $this->simpan($umkm, ['nib' => '1234567890123', 'business_link' => ''])->assertSessionHasNoErrors();

        $this->assertSame('1234567890123', $this->profil($umkm)->nib);
        $this->assertNull($this->profil($umkm)->business_link);
    }

    public function test_cukup_tautan_tanpa_nib_dapat_disimpan(): void
    {
        $umkm = $this->umkm();

        $this->simpan($umkm, ['nib' => '', 'business_link' => 'tokopedia.com/kopi'])->assertSessionHasNoErrors();

        $this->assertNull($this->profil($umkm)->nib);
        $this->assertSame('tokopedia.com/kopi', $this->profil($umkm)->business_link);
    }

    // =====================================================================
    // Negatif
    // =====================================================================

    public function test_nama_usaha_wajib_diisi(): void
    {
        $umkm = $this->umkm();

        $this->simpan($umkm, ['business_name' => ''])
            ->assertSessionHasErrors(['business_name' => 'Nama usaha wajib diisi.']);

        $this->assertSame('Kopi Nusantara', $this->profil($umkm)->business_name);
    }

    public function test_setiap_isian_wajib_ditolak_bila_kosong(): void
    {
        $umkm = $this->umkm();

        foreach (['business_name', 'business_field', 'description', 'contact_phone', 'city', 'address'] as $field) {
            $this->simpan($umkm, [$field => ''])->assertSessionHasErrors($field);
        }
    }

    public function test_nib_dan_tautan_sama_sama_kosong_ditolak(): void
    {
        $umkm = $this->umkm();

        $this->simpan($umkm, ['nib' => '', 'business_link' => ''])
            ->assertSessionHasErrors(['nib' => 'Isi NIB atau tautan informasi usaha.', 'business_link']);

        $this->assertSame('9120304567812', $this->profil($umkm)->nib);
    }

    public function test_nib_berisi_huruf_ditolak_walau_tautan_terisi(): void
    {
        $this->simpan($this->umkm(), ['nib' => '12345abcde123'])
            ->assertSessionHasErrors(['nib' => 'NIB harus terdiri dari 13 digit angka.']);
    }

    public function test_nomor_kontak_berisi_huruf_ditolak(): void
    {
        $this->simpan($this->umkm(), ['contact_phone' => '0812abc'])->assertSessionHasErrors('contact_phone');
    }

    // =====================================================================
    // Batas (boundary)
    // =====================================================================

    public function test_nib_harus_tepat_13_digit(): void
    {
        $umkm = $this->umkm();

        $this->simpan($umkm, ['nib' => '123456789012'])->assertSessionHasErrors('nib');     // 12 digit
        $this->simpan($umkm, ['nib' => '12345678901234'])->assertSessionHasErrors('nib');   // 14 digit
        $this->simpan($umkm, ['nib' => '1234567890123'])->assertSessionHasNoErrors();       // 13 digit
    }

    public function test_batas_panjang_nama_usaha_150_karakter(): void
    {
        $umkm = $this->umkm();

        $this->simpan($umkm, ['business_name' => str_repeat('a', 151)])->assertSessionHasErrors('business_name');
        $this->simpan($umkm, ['business_name' => str_repeat('a', 150)])->assertSessionHasNoErrors();
    }

    public function test_batas_panjang_kontak_20_dan_deskripsi_1000_karakter(): void
    {
        $umkm = $this->umkm();

        $this->simpan($umkm, ['contact_phone' => str_repeat('1', 21)])->assertSessionHasErrors('contact_phone');
        $this->simpan($umkm, ['contact_phone' => str_repeat('1', 20)])->assertSessionHasNoErrors();
        $this->simpan($umkm, ['description' => str_repeat('d', 1001)])->assertSessionHasErrors('description');
        $this->simpan($umkm, ['description' => str_repeat('d', 1000)])->assertSessionHasNoErrors();
    }

    public function test_tautan_hanya_spasi_dianggap_kosong(): void
    {
        $this->simpan($this->umkm(), ['nib' => '', 'business_link' => '   '])
            ->assertSessionHasErrors(['nib', 'business_link']);
    }

    // =====================================================================
    // Aturan perubahan status verifikasi
    // =====================================================================

    public function test_ubah_nama_usaha_pada_profil_terverifikasi_mereset_ke_menunggu(): void
    {
        $umkm = $this->umkm();

        $this->simpan($umkm, ['business_name' => 'Kopi Nusantara Baru'])
            ->assertSessionHas('success', 'Profil usaha berhasil disimpan. Status verifikasi kembali menjadi Menunggu Pemeriksaan.');

        $profile = $this->profil($umkm);
        $this->assertSame(VerificationStatus::Pending, $profile->verification_status);
        $this->assertNull($profile->verified_at);
    }

    public function test_ubah_nib_pada_profil_terverifikasi_mereset_ke_menunggu(): void
    {
        $umkm = $this->umkm();

        $this->simpan($umkm, ['nib' => '1111111111111']);

        $this->assertSame(VerificationStatus::Pending, $this->profil($umkm)->verification_status);
        $this->assertNull($this->profil($umkm)->verified_at);
    }

    public function test_ubah_tautan_pada_profil_terverifikasi_mereset_ke_menunggu(): void
    {
        $umkm = $this->umkm();

        $this->simpan($umkm, ['business_link' => 'instagram.com/akun.baru']);

        $this->assertSame(VerificationStatus::Pending, $this->profil($umkm)->verification_status);
        $this->assertNull($this->profil($umkm)->verified_at);
    }

    public function test_ubah_kontak_saja_tidak_mereset_status_terverifikasi(): void
    {
        $umkm = $this->umkm();
        $verifiedAt = $this->profil($umkm)->verified_at;

        $this->simpan($umkm, ['contact_phone' => '085611112222'])
            ->assertSessionHas('success', 'Profil usaha berhasil disimpan.');

        $profile = $this->profil($umkm);
        $this->assertSame(VerificationStatus::Verified, $profile->verification_status);
        $this->assertEquals($verifiedAt, $profile->verified_at);
        $this->assertSame('085611112222', $profile->contact_phone);
    }

    public function test_ubah_alamat_kota_bidang_atau_deskripsi_saja_tidak_mereset_status(): void
    {
        $umkm = $this->umkm();

        $this->simpan($umkm, [
            'address' => 'Jl. Baru No. 9', 'city' => 'Cimahi', 'business_field' => 'Fashion', 'description' => 'Deskripsi baru.',
        ]);

        $this->assertSame(VerificationStatus::Verified, $this->profil($umkm)->verification_status);
        $this->assertSame('Cimahi', $this->profil($umkm)->city);
    }

    public function test_simpan_tanpa_perubahan_tidak_mereset_status_terverifikasi(): void
    {
        $umkm = $this->umkm();

        $this->simpan($umkm);

        $this->assertSame(VerificationStatus::Verified, $this->profil($umkm)->verification_status);
    }

    public function test_profil_menunggu_tetap_menunggu_setelah_diubah(): void
    {
        $umkm = $this->umkm(VerificationStatus::Pending);

        $this->simpan($umkm, ['business_name' => 'Nama Lain']);

        $this->assertSame(VerificationStatus::Pending, $this->profil($umkm)->verification_status);
    }

    public function test_profil_ditolak_yang_diperbaiki_diajukan_ulang_dan_alasan_dibersihkan(): void
    {
        $umkm = $this->umkm(VerificationStatus::Rejected, ['rejection_reason' => 'NIB tidak sesuai.']);

        $this->simpan($umkm, ['nib' => '2222222222222']);

        $profile = $this->profil($umkm);
        $this->assertSame(VerificationStatus::Pending, $profile->verification_status);
        $this->assertNull($profile->rejection_reason);
    }

    public function test_profil_ditolak_disimpan_tanpa_perubahan_tetap_ditolak(): void
    {
        $umkm = $this->umkm(VerificationStatus::Rejected, ['rejection_reason' => 'NIB tidak sesuai.']);

        $this->simpan($umkm);

        $profile = $this->profil($umkm);
        $this->assertSame(VerificationStatus::Rejected, $profile->verification_status);
        $this->assertSame('NIB tidak sesuai.', $profile->rejection_reason);
    }

    // =====================================================================
    // Tampilan status verifikasi dan rating (data dari database)
    // =====================================================================

    public function test_label_status_mengikuti_data_database(): void
    {
        $pending = $this->actingAs($this->umkm(VerificationStatus::Pending))->get(route('umkm.profil.edit'));
        $this->assertBadgeStatus($pending, 'Menunggu Pemeriksaan');
        $pending->assertDontSee('Alasan penolakan');

        $verified = $this->actingAs($this->umkm(VerificationStatus::Verified))->get(route('umkm.profil.edit'));
        $this->assertBadgeStatus($verified, 'Terverifikasi');
        $verified->assertDontSee('Alasan penolakan');
    }

    public function test_status_ditolak_menampilkan_alasan_penolakan_dari_database(): void
    {
        $umkm = $this->umkm(VerificationStatus::Rejected, ['rejection_reason' => 'NIB tidak sesuai dengan nama usaha.']);

        $response = $this->actingAs($umkm)->get(route('umkm.profil.edit'));

        $this->assertBadgeStatus($response, 'Ditolak');
        $response->assertSee('Alasan penolakan')->assertSee('NIB tidak sesuai dengan nama usaha.');
    }

    public function test_rating_dihitung_dari_evaluasi_yang_diterima_umkm(): void
    {
        $umkm = $this->umkm();
        $lain = $this->umkm();

        foreach ([5, 4, 4] as $rating) {
            $this->addRatingReceivedByUmkm($umkm, $rating);
        }
        $this->addRatingGivenByUmkm($umkm, 1);        // diberikan oleh UMKM, tidak boleh dihitung
        $this->addRatingReceivedByUmkm($lain, 1);     // milik UMKM lain, tidak boleh dihitung

        $response = $this->actingAs($umkm)->get(route('umkm.profil.edit'));

        $response->assertOk()
            ->assertSee('dusk="nilai-rating">4,3</span>', false)
            ->assertSee('berdasarkan 3 evaluasi')
            ->assertSee('dusk="jumlah-bintang-5">1</span>', false)
            ->assertSee('dusk="jumlah-bintang-4">2</span>', false)
            ->assertSee('dusk="jumlah-bintang-1">0</span>', false);
    }

    public function test_tanpa_evaluasi_menampilkan_belum_ada_penilaian(): void
    {
        $this->actingAs($this->umkm())->get(route('umkm.profil.edit'))
            ->assertOk()
            ->assertSee('Belum ada penilaian.')
            ->assertDontSee('berdasarkan');
    }

    // =====================================================================
    // Otorisasi
    // =====================================================================

    public function test_kandidat_tidak_dapat_membuka_atau_mengubah_profil_umkm(): void
    {
        $umkm = $this->umkm();
        $kandidat = $this->makeKandidat();

        $this->actingAs($kandidat)->get(route('umkm.profil.edit'))->assertForbidden();
        $this->actingAs($kandidat)->put(route('umkm.profil.update'), $this->profilePayload(['business_name' => 'Diretas']))
            ->assertForbidden();

        $this->assertSame('Kopi Nusantara', $this->profil($umkm)->business_name);
    }

    public function test_admin_tidak_dapat_membuka_atau_mengubah_profil_umkm(): void
    {
        $umkm = $this->umkm();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('umkm.profil.edit'))->assertForbidden();
        $this->actingAs($admin)->put(route('umkm.profil.update'), $this->profilePayload(['business_name' => 'Diretas']))
            ->assertForbidden();

        $this->assertSame('Kopi Nusantara', $this->profil($umkm)->business_name);
    }

    public function test_tamu_dialihkan_ke_login(): void
    {
        $this->stubLoginRoute();

        $this->get(route('umkm.profil.edit'))->assertRedirect(route('login'));
        $this->put(route('umkm.profil.update'), $this->profilePayload())->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_umkm_tidak_dapat_mengubah_profil_umkm_lain(): void
    {
        $penyerang = $this->umkm(profile: ['business_name' => 'Usaha Penyerang']);
        $korban = $this->umkm(profile: ['business_name' => 'Usaha Korban']);
        $profilKorban = $this->profil($korban);

        // Penyerang menyisipkan id/user_id milik korban pada form.
        $this->actingAs($penyerang)->put(route('umkm.profil.update'), $this->profilePayload([
            'business_name' => 'Dibajak',
            'user_id' => $korban->id,
            'id' => $profilKorban->id,
            'umkm_profile_id' => $profilKorban->id,
        ]))->assertRedirect(route('umkm.profil.edit'));

        $this->assertSame('Usaha Korban', $this->profil($korban)->business_name);
        $this->assertSame('Dibajak', $this->profil($penyerang)->business_name);
        $this->assertSame($penyerang->id, $this->profil($penyerang)->user_id);
        $this->assertSame($korban->id, $this->profil($korban)->user_id);
    }

    public function test_tidak_ada_route_untuk_mengakses_profil_berdasarkan_id(): void
    {
        $penyerang = $this->umkm();
        $profilKorban = $this->profil($this->umkm(profile: ['business_name' => 'Usaha Korban']));

        $this->actingAs($penyerang)->get('/umkm/profil/'.$profilKorban->id)->assertNotFound();
        $this->actingAs($penyerang)->put('/umkm/profil/'.$profilKorban->id, $this->profilePayload(['business_name' => 'Dibajak']))
            ->assertNotFound();

        $this->assertSame('Usaha Korban', $profilKorban->fresh()->business_name);
    }

    public function test_halaman_tidak_menampilkan_data_umkm_lain(): void
    {
        $saya = $this->umkm(profile: ['business_name' => 'Usaha Saya']);
        $this->umkm(profile: ['business_name' => 'Usaha Orang Lain', 'nib' => '5555555555555']);

        $this->actingAs($saya)->get(route('umkm.profil.edit'))
            ->assertSee('Usaha Saya')
            ->assertDontSee('Usaha Orang Lain')
            ->assertDontSee('5555555555555');
    }

    public function test_status_dan_alasan_penolakan_tidak_dapat_diubah_lewat_form(): void
    {
        $umkm = $this->umkm(VerificationStatus::Pending);

        $this->simpan($umkm, [
            'verification_status' => 'verified',
            'verified_at' => now()->toDateTimeString(),
            'rejection_reason' => 'disusupkan',
        ]);

        $profile = $this->profil($umkm);
        $this->assertSame(VerificationStatus::Pending, $profile->verification_status);
        $this->assertNull($profile->verified_at);
        $this->assertNull($profile->rejection_reason);
    }

    // =====================================================================
    // Regresi
    // =====================================================================

    public function test_route_profil_usaha_lama_sudah_hilang_dan_route_baru_ada(): void
    {
        $this->assertTrue(Route::has('umkm.profil.edit'));
        $this->assertTrue(Route::has('umkm.profil.update'));
        $this->assertFalse(Route::has('profil-usaha'));
        $this->assertFalse(Route::has('profil-usaha.update'));
        $this->assertFalse(Route::has('umkm.profil-usaha'));
        $this->assertFalse(Route::has('umkm.profil-usaha.update'));

        $this->actingAs($this->umkm())->get('/profil-usaha')->assertNotFound();
    }

    public function test_halaman_awal_umkm_dan_menu_sidebar_menunjuk_ke_profil_usaha(): void
    {
        $umkm = $this->umkm();

        $this->assertSame('umkm.profil.edit', $umkm->homeRoute());
        $this->assertTrue(Route::has($umkm->homeRoute()));

        $this->actingAs($umkm)->get(route('umkm.seleksi.index'))
            ->assertOk()
            ->assertSee(route('umkm.profil.edit'), false);
    }

    public function test_halaman_umkm_lain_yang_sudah_ada_tetap_berfungsi(): void
    {
        $umkm = $this->umkm();

        $this->actingAs($umkm)->get(route('umkm.seleksi.index'))->assertOk();
        $this->actingAs($umkm)->get(route('umkm.proyek.create'))->assertOk();
    }
}
