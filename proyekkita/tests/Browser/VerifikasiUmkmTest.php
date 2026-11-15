<?php

namespace Tests\Browser;

use App\Enums\VerificationStatus;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Concerns\BuildsApplicationScenario;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

/**
 * [PBI-08 / FR-05] Verifikasi akun UMKM oleh admin melalui browser.
 * Kode skenario D08-xx dipakai di laporan pengujian. Database: lihat UsesDuskTestDatabase.
 */
class VerifikasiUmkmTest extends DuskTestCase
{
    use BuildsApplicationScenario, UsesDuskTestDatabase;

    private function pendingProfile(array $o = [])
    {
        return $this->makeUmkm($o + [
            'business_name' => 'Laundry Bersih Kilat',
            'nib' => '8120015566702',
            'verification_status' => VerificationStatus::Pending,
        ])->umkmProfile()->first();
    }

    /** D08-01 */
    public function test_admin_menyetujui_umkm(): void
    {
        $admin = User::factory()->admin()->create();
        $profile = $this->pendingProfile();

        $this->browse(function (Browser $browser) use ($admin, $profile) {
            $browser->loginAs($admin)
                ->visit(route('admin.verifikasi.index', ['usaha' => $profile->id]))
                ->assertSeeIn('@detail-usaha', 'Laundry Bersih Kilat')
                ->press('@btn-setujui')
                ->waitForText('Akun UMKM berhasil diverifikasi.');
        });

        $this->assertSame(VerificationStatus::Verified, $profile->fresh()->verification_status);
    }

    /** D08-02 */
    public function test_penolakan_tanpa_alasan_menampilkan_pesan_validasi(): void
    {
        $admin = User::factory()->admin()->create();
        $profile = $this->pendingProfile();

        $this->browse(function (Browser $browser) use ($admin, $profile) {
            $browser->loginAs($admin)
                ->visit(route('admin.verifikasi.index', ['usaha' => $profile->id]))
                ->press('@btn-tolak')
                ->waitFor('@galat-alasan')
                ->assertSeeIn('@galat-alasan', 'Alasan penolakan wajib diisi.');
        });

        $this->assertSame(VerificationStatus::Pending, $profile->fresh()->verification_status);
    }

    /** D08-03 */
    public function test_admin_menolak_dengan_alasan_dan_umkm_melihat_alasannya(): void
    {
        $admin = User::factory()->admin()->create();
        $profile = $this->pendingProfile();
        $umkm = $profile->user;

        $this->browse(function (Browser $browser) use ($admin, $umkm, $profile) {
            $browser->loginAs($admin)
                ->visit(route('admin.verifikasi.index', ['usaha' => $profile->id]))
                ->type('@alasan-penolakan', 'NIB tidak sesuai dengan nama usaha.')
                ->press('@btn-tolak')
                ->waitForText('Verifikasi UMKM ditolak.')
                ->logout()
                ->loginAs($umkm)
                ->visit(route('umkm.profil.edit'))
                ->assertSeeIn('@status-verifikasi', 'Ditolak')
                ->assertSeeIn('@alasan-penolakan-umkm', 'NIB tidak sesuai dengan nama usaha.');
        });
    }

    /** D08-04 (admin memilih usaha dari antrean) */
    public function test_admin_memilih_usaha_dari_antrean(): void
    {
        $admin = User::factory()->admin()->create();
        $pertama = $this->pendingProfile(['business_name' => 'Usaha Pertama', 'nib' => '1111111111111']);
        $kedua = $this->pendingProfile(['business_name' => 'Usaha Kedua', 'nib' => '2222222222222']);

        $this->browse(function (Browser $browser) use ($admin, $pertama, $kedua) {
            $browser->loginAs($admin)
                ->visit(route('admin.verifikasi.index'))
                ->assertSeeIn('@detail-usaha', 'Usaha Pertama')
                ->click("@item-usaha-{$kedua->id}")
                ->waitForTextIn('@detail-nib', '2222222222222')
                ->assertSeeIn('@detail-usaha', 'Usaha Kedua');
        });
    }

    /** D08-05 (pindah tab status: tombol keputusan hanya untuk antrean Menunggu) */
    public function test_admin_berpindah_antrean_status(): void
    {
        $admin = User::factory()->admin()->create();
        $this->pendingProfile(['business_name' => 'Usaha Menunggu']);
        $this->pendingProfile(['business_name' => 'Usaha Terverifikasi', 'verification_status' => VerificationStatus::Verified, 'verified_at' => now()]);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit(route('admin.verifikasi.index'))
                ->assertPresent('@btn-setujui')
                ->click('@antrean-verified')
                ->waitForTextIn('@detail-usaha', 'Usaha Terverifikasi')
                ->assertMissing('@btn-setujui')
                ->assertMissing('@btn-tolak');
        });
    }

    /** D08-06 (antrean kosong) */
    public function test_antrean_kosong_menampilkan_pesan(): void
    {
        $admin = User::factory()->admin()->create();

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit(route('admin.verifikasi.index'))
                ->assertSeeIn('@antrean-kosong', 'Tidak ada usaha pada antrean ini.');
        });
    }

    /** D08-07 (UMKM menerima notifikasi setelah disetujui: badge belum dibaca muncul) */
    public function test_umkm_menerima_notifikasi_setelah_disetujui(): void
    {
        $admin = User::factory()->admin()->create();
        $profile = $this->pendingProfile();
        $umkm = $profile->user;

        $this->browse(function (Browser $browser) use ($admin, $umkm, $profile) {
            $browser->loginAs($admin)
                ->visit(route('admin.verifikasi.index', ['usaha' => $profile->id]))
                ->press('@btn-setujui')
                ->waitForText('Akun UMKM berhasil diverifikasi.')
                ->logout()
                ->loginAs($umkm)
                ->visit(route('umkm.profil.edit'))
                ->assertSeeIn('@status-verifikasi', 'Terverifikasi')
                ->assertSourceHas('<span class="cnt">1</span>');
        });
    }

    /** D08-08 (otorisasi: kandidat dan UMKM tidak boleh membuka antrean) */
    public function test_non_admin_tidak_dapat_membuka_antrean_verifikasi(): void
    {
        $kandidat = $this->makeKandidat();
        $umkm = $this->makeUmkm();

        $this->browse(function (Browser $browser) use ($kandidat, $umkm) {
            $browser->loginAs($kandidat)
                ->visit(route('admin.verifikasi.index'))
                ->assertSee('403')
                ->logout()
                ->loginAs($umkm)
                ->visit(route('admin.verifikasi.index'))
                ->assertSee('403');
        });
    }

    /** D08-09 (alur utama lintas PBI-01 -> PBI-08 -> PBI-01) */
    public function test_alur_umkm_mengisi_profil_admin_menyetujui_umkm_mengubah_nama(): void
    {
        $admin = User::factory()->admin()->create();
        $umkm = User::factory()->umkm()->create();

        $this->browse(function (Browser $browser) use ($admin, $umkm) {
            // PBI-01: UMKM baru mengisi profil usaha.
            $browser->loginAs($umkm)
                ->visit(route('umkm.profil.edit'))
                ->type('@nama-usaha', 'Warung Alur Dusk')
                ->type('@bidang-usaha', 'Kuliner & Minuman')
                ->type('@deskripsi-usaha', 'Warung makan uji alur.')
                ->type('@kontak-usaha', '081234567890')
                ->type('@kota-usaha', 'Bandung')
                ->type('@alamat-usaha', 'Jl. Uji Coba No. 1')
                ->type('@nib', '9120304567812')
                ->press('@simpan-profil')
                ->waitForText('Profil usaha berhasil disimpan.')
                ->assertSeeIn('@status-verifikasi', 'Menunggu Pemeriksaan')

                // PBI-08: admin menyetujui.
                ->logout()
                ->loginAs($admin)
                ->visit(route('admin.verifikasi.index'))
                ->assertSeeIn('@detail-usaha', 'Warung Alur Dusk')
                ->press('@btn-setujui')
                ->waitForText('Akun UMKM berhasil diverifikasi.')

                // PBI-01: UMKM melihat status, lalu mengubah nama usaha -> verifikasi ulang.
                ->logout()
                ->loginAs($umkm)
                ->visit(route('umkm.profil.edit'))
                ->assertSeeIn('@status-verifikasi', 'Terverifikasi')
                ->type('@nama-usaha', 'Warung Alur Dusk Baru')
                ->press('@simpan-profil')
                ->waitForText('Profil usaha berhasil disimpan.')
                ->assertSeeIn('@status-verifikasi', 'Menunggu Pemeriksaan');
        });
    }
}
