<?php

namespace Tests\Browser;

use App\Enums\VerificationStatus;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Concerns\BuildsUmkmProfileScenario;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

/**
 * [PBI-01 / FR-02] UMKM mengelola profil usaha dan melihat status verifikasi serta rating melalui browser.
 *
 * Pelengkap Feature Test (tests/Feature/Umkm/ProfilUsahaTest.php), bukan penggantinya.
 * Selector memakai atribut dusk pada resources/views/umkm/profil/edit.blade.php.
 * Prasyarat lingkungan sama dengan Dusk PBI-16 s.d. PBI-18 (lihat UsesDuskTestDatabase): server aplikasi harus
 * memakai database/dusk.sqlite yang sama dengan proses test.
 */
class ProfilUsahaTest extends DuskTestCase
{
    use BuildsUmkmProfileScenario;
    use UsesDuskTestDatabase;

    private const PESAN_SIMPAN = 'Profil usaha berhasil disimpan.';

    private function umkm(VerificationStatus $status = VerificationStatus::Verified, array $profile = []): User
    {
        return $this->makeUmkm(array_merge($this->profilePayload(), [
            'verification_status' => $status,
            'verified_at' => $status === VerificationStatus::Verified ? now() : null,
        ], $profile));
    }

    private function profil(User $user)
    {
        return $user->umkmProfile()->first();
    }

    private function buka(Browser $browser, User $user): Browser
    {
        return $browser->loginAs($user)
            ->visit(route('umkm.profil.edit'))
            ->assertTitleContains('Profil Usaha');   // gagal jelas bila server/URL/database Dusk salah
    }

    // =====================================================================
    // UMKM dapat membuka dan memperbarui profil
    // =====================================================================

    public function test_umkm_dapat_membuka_dan_memperbarui_profil(): void
    {
        $umkm = $this->umkm();

        $this->browse(function (Browser $browser) use ($umkm) {
            $this->buka($browser, $umkm)
                ->assertInputValue('@nama-usaha', 'Kopi Nusantara')
                ->assertInputValue('@bidang-usaha', 'Kuliner & Minuman')
                ->assertInputValue('@kontak-usaha', '081234567890')
                ->assertInputValue('@kota-usaha', 'Bandung')
                ->assertInputValue('@nib', '9120304567812')
                ->assertSeeIn('@status-verifikasi', 'Terverifikasi')
                ->type('@deskripsi-usaha', 'Roastery dan kedai kopi lokal.')
                ->type('@kontak-usaha', '085611112222')
                ->type('@kota-usaha', 'Cimahi')
                ->type('@alamat-usaha', 'Jl. Baru No. 9, Cimahi')
                ->press('@simpan-profil')
                ->waitForText(self::PESAN_SIMPAN)
                ->assertInputValue('@kontak-usaha', '085611112222')
                ->assertInputValue('@kota-usaha', 'Cimahi')
                ->assertInputValue('@alamat-usaha', 'Jl. Baru No. 9, Cimahi');
        });

        $profile = $this->profil($umkm);
        $this->assertSame('Cimahi', $profile->city);
        $this->assertSame('085611112222', $profile->contact_phone);
        $this->assertSame('Roastery dan kedai kopi lokal.', $profile->description);
    }

    // =====================================================================
    // Validasi
    // =====================================================================

    public function test_nama_usaha_wajib_diisi(): void
    {
        $umkm = $this->umkm();

        $this->browse(function (Browser $browser) use ($umkm) {
            $this->buka($browser, $umkm)
                ->clear('@nama-usaha')
                ->press('@simpan-profil')
                ->waitFor('@galat-nama-usaha')
                ->assertSeeIn('@galat-nama-usaha', 'Nama usaha wajib diisi.');
        });

        $this->assertSame('Kopi Nusantara', $this->profil($umkm)->business_name);
    }

    public function test_nib_harus_13_digit_jika_diisi(): void
    {
        $umkm = $this->umkm();

        $this->browse(function (Browser $browser) use ($umkm) {
            // NIB 5 digit ditolak dan data lama tetap utuh.
            $this->buka($browser, $umkm)
                ->type('@nib', '12345')
                ->press('@simpan-profil')
                ->waitFor('@galat-nib')
                ->assertSeeIn('@galat-nib', 'NIB harus terdiri dari 13 digit angka.')
                ->assertDontSee(self::PESAN_SIMPAN);

            $this->assertSame('9120304567812', $this->profil($umkm)->nib);

            // NIB 13 digit diterima.
            $browser->type('@nib', '1234567890123')
                ->press('@simpan-profil')
                ->waitForText(self::PESAN_SIMPAN)
                ->assertInputValue('@nib', '1234567890123');
        });

        $this->assertSame('1234567890123', $this->profil($umkm)->nib);
    }

    public function test_nib_dan_tautan_sama_sama_kosong_ditolak(): void
    {
        $umkm = $this->umkm();

        $this->browse(function (Browser $browser) use ($umkm) {
            $this->buka($browser, $umkm)
                ->clear('@nib')
                ->clear('@tautan-usaha')
                ->press('@simpan-profil')
                ->waitFor('@galat-nib')
                ->assertSeeIn('@galat-nib', 'Isi NIB atau tautan informasi usaha.')
                ->assertSeeIn('@galat-tautan', 'Isi tautan informasi usaha atau NIB.')
                ->assertDontSee(self::PESAN_SIMPAN);
        });

        $this->assertSame('9120304567812', $this->profil($umkm)->nib);
    }

    public function test_cukup_nib_tanpa_tautan_dapat_disimpan(): void
    {
        $umkm = $this->umkm();

        $this->browse(function (Browser $browser) use ($umkm) {
            $this->buka($browser, $umkm)
                ->clear('@tautan-usaha')
                ->press('@simpan-profil')
                ->waitForText(self::PESAN_SIMPAN)
                ->assertInputValue('@tautan-usaha', '');
        });

        $this->assertNull($this->profil($umkm)->business_link);
    }

    public function test_cukup_tautan_tanpa_nib_dapat_disimpan(): void
    {
        $umkm = $this->umkm(profile: ['nib' => null]);

        $this->browse(function (Browser $browser) use ($umkm) {
            $this->buka($browser, $umkm)
                ->assertInputValue('@nib', '')
                ->press('@simpan-profil')
                ->waitForText(self::PESAN_SIMPAN)
                ->assertInputValue('@nib', '');
        });

        $this->assertNull($this->profil($umkm)->nib);
        $this->assertSame('instagram.com/kopinusantara', $this->profil($umkm)->business_link);
    }

    // =====================================================================
    // Aturan reset status verifikasi
    // =====================================================================

    public function test_ubah_nama_usaha_mereset_status_verified_menjadi_pending(): void
    {
        $umkm = $this->umkm();

        $this->browse(function (Browser $browser) use ($umkm) {
            $this->buka($browser, $umkm)
                ->assertSeeIn('@status-verifikasi', 'Terverifikasi')
                ->type('@nama-usaha', 'Kopi Nusantara Baru')
                ->press('@simpan-profil')
                ->waitForText(self::PESAN_SIMPAN)
                ->assertSee('Status verifikasi kembali menjadi Menunggu Pemeriksaan.')
                ->assertSeeIn('@status-verifikasi', 'Menunggu Pemeriksaan');
        });

        $this->assertSame(VerificationStatus::Pending, $this->profil($umkm)->verification_status);
        $this->assertNull($this->profil($umkm)->verified_at);
    }

    public function test_ubah_nib_mereset_status_verified_menjadi_pending(): void
    {
        $umkm = $this->umkm();

        $this->browse(function (Browser $browser) use ($umkm) {
            $this->buka($browser, $umkm)
                ->type('@nib', '1111111111111')
                ->press('@simpan-profil')
                ->waitForText(self::PESAN_SIMPAN)
                ->assertSeeIn('@status-verifikasi', 'Menunggu Pemeriksaan');
        });

        $this->assertSame(VerificationStatus::Pending, $this->profil($umkm)->verification_status);
    }

    public function test_ubah_tautan_usaha_mereset_status_verified_menjadi_pending(): void
    {
        $umkm = $this->umkm();

        $this->browse(function (Browser $browser) use ($umkm) {
            $this->buka($browser, $umkm)
                ->type('@tautan-usaha', 'instagram.com/akun.baru')
                ->press('@simpan-profil')
                ->waitForText(self::PESAN_SIMPAN)
                ->assertSeeIn('@status-verifikasi', 'Menunggu Pemeriksaan');
        });

        $this->assertSame(VerificationStatus::Pending, $this->profil($umkm)->verification_status);
    }

    public function test_ubah_kontak_atau_alamat_saja_tidak_mereset_status_verified(): void
    {
        $umkm = $this->umkm();

        $this->browse(function (Browser $browser) use ($umkm) {
            $this->buka($browser, $umkm)
                ->type('@kontak-usaha', '085611112222')
                ->type('@alamat-usaha', 'Jl. Pindah No. 7, Bandung')
                ->press('@simpan-profil')
                ->waitForText(self::PESAN_SIMPAN)
                ->assertDontSee('Status verifikasi kembali menjadi Menunggu Pemeriksaan.')
                ->assertSeeIn('@status-verifikasi', 'Terverifikasi');
        });

        $profile = $this->profil($umkm);
        $this->assertSame(VerificationStatus::Verified, $profile->verification_status);
        $this->assertNotNull($profile->verified_at);
        $this->assertSame('085611112222', $profile->contact_phone);
    }

    // =====================================================================
    // Status Rejected menampilkan alasan penolakan
    // =====================================================================

    public function test_status_rejected_menampilkan_alasan_penolakan_dari_database(): void
    {
        $umkm = $this->umkm(VerificationStatus::Rejected, ['rejection_reason' => 'NIB tidak sesuai dengan nama usaha.']);

        $this->browse(function (Browser $browser) use ($umkm) {
            $this->buka($browser, $umkm)
                ->assertSeeIn('@status-verifikasi', 'Ditolak')
                ->assertSeeIn('@alasan-penolakan-umkm', 'NIB tidak sesuai dengan nama usaha.');
        });
    }

    public function test_profil_ditolak_yang_diperbaiki_diajukan_ulang_dan_alasan_hilang(): void
    {
        $umkm = $this->umkm(VerificationStatus::Rejected, ['rejection_reason' => 'NIB tidak sesuai dengan nama usaha.']);

        $this->browse(function (Browser $browser) use ($umkm) {
            $this->buka($browser, $umkm)
                ->type('@nib', '2222222222222')
                ->press('@simpan-profil')
                ->waitForText(self::PESAN_SIMPAN)
                ->assertSeeIn('@status-verifikasi', 'Menunggu Pemeriksaan')
                ->assertMissing('@alasan-penolakan-umkm');
        });

        $this->assertNull($this->profil($umkm)->rejection_reason);
    }

    // =====================================================================
    // Otorisasi
    // =====================================================================

    public function test_kandidat_tidak_dapat_mengakses_profil_umkm(): void
    {
        $this->umkm();
        $kandidat = $this->makeKandidat();

        $this->browse(function (Browser $browser) use ($kandidat) {
            $browser->loginAs($kandidat)
                ->visit(route('umkm.profil.edit'))
                ->assertSee('403')
                ->assertDontSee('Informasi Usaha')
                ->assertMissing('@simpan-profil');
        });
    }

    public function test_admin_tidak_dapat_mengakses_profil_umkm(): void
    {
        $admin = User::factory()->admin()->create();

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit(route('umkm.profil.edit'))
                ->assertSee('403')
                ->assertMissing('@simpan-profil');
        });
    }

    public function test_umkm_tidak_dapat_mengubah_profil_umkm_lain(): void
    {
        $penyerang = $this->umkm(VerificationStatus::Pending, ['business_name' => 'Usaha Penyerang']);
        $korban = $this->umkm(VerificationStatus::Verified, ['business_name' => 'Usaha Korban']);
        $profilKorban = $this->profil($korban);

        $this->browse(function (Browser $browser) use ($penyerang, $korban, $profilKorban) {
            // 1) Halaman milik sendiri tidak memuat data usaha lain.
            $this->buka($browser, $penyerang)
                ->assertInputValue('@nama-usaha', 'Usaha Penyerang')
                ->assertDontSee('Usaha Korban');

            // 2) Tidak ada halaman profil berdasarkan id.
            $browser->visit('/umkm/profil/'.$profilKorban->id)->assertSee('404');

            // 3) Form dimanipulasi: id/user_id milik korban dan status "verified" disisipkan, lalu disimpan.
            $browser->visit(route('umkm.profil.edit'))
                ->script(sprintf(
                    "var f = document.querySelector('main form');".
                    "[['user_id', '%1\$d'], ['id', '%2\$d'], ['umkm_profile_id', '%2\$d'], ['verification_status', 'verified']].forEach(function (p) {".
                    "var i = document.createElement('input'); i.type = 'hidden'; i.name = p[0]; i.value = p[1]; f.appendChild(i); });",
                    $korban->id,
                    $profilKorban->id
                ));

            $browser->type('@nama-usaha', 'Dibajak')
                ->press('@simpan-profil')
                ->waitForText(self::PESAN_SIMPAN)
                ->assertSeeIn('@status-verifikasi', 'Menunggu Pemeriksaan');
        });

        // Profil korban utuh; profil penyerang berubah dan tetap Pending walau dipaksa "verified".
        $this->assertSame('Usaha Korban', $this->profil($korban)->business_name);
        $this->assertSame(VerificationStatus::Verified, $this->profil($korban)->verification_status);
        $this->assertSame('Dibajak', $this->profil($penyerang)->business_name);
        $this->assertSame(VerificationStatus::Pending, $this->profil($penyerang)->verification_status);
        $this->assertSame($penyerang->id, $this->profil($penyerang)->user_id);
    }

    // =====================================================================
    // Rating sesuai data sebenarnya
    // =====================================================================

    public function test_rating_ditampilkan_sesuai_data_evaluasi_di_database(): void
    {
        $umkm = $this->umkm();
        $lain = $this->umkm();

        foreach ([5, 4, 4] as $rating) {
            $this->addRatingReceivedByUmkm($umkm, $rating);
        }
        $this->addRatingGivenByUmkm($umkm, 1);     // diberikan oleh UMKM ini: tidak dihitung
        $this->addRatingReceivedByUmkm($lain, 1);  // milik UMKM lain: tidak dihitung

        $this->browse(function (Browser $browser) use ($umkm) {
            $this->buka($browser, $umkm)
                ->assertSeeIn('@nilai-rating', '4,3')
                ->assertSeeIn('@jumlah-evaluasi', 'berdasarkan 3 evaluasi')
                ->assertSeeIn('@jumlah-bintang-5', '1')
                ->assertSeeIn('@jumlah-bintang-4', '2')
                ->assertSeeIn('@jumlah-bintang-1', '0');
        });
    }

    public function test_tanpa_evaluasi_menampilkan_belum_ada_penilaian(): void
    {
        $umkm = $this->umkm();

        $this->browse(function (Browser $browser) use ($umkm) {
            $this->buka($browser, $umkm)
                ->assertSeeIn('@rata-rata-rating', 'Belum ada penilaian.')
                ->assertMissing('@nilai-rating');
        });
    }
}
