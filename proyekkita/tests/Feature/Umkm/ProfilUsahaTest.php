<?php

namespace Tests\Browser;

use App\Enums\VerificationStatus;
use App\Models\UmkmProfile;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

class ProfilUsahaTest extends DuskTestCase
{
    use UsesDuskTestDatabase;

    public function test_tc_pbi1_001_menampilkan_profil_usaha_terverifikasi(): void
    {
        $umkmUser = User::factory()->umkm()->create([
            'email' => 'umkm@proyekkita.test',
        ]);

        $umkmUser->umkmProfile()->create([
            'business_name' => 'Kopi Nusantara',
            'business_field' => 'Kuliner & Minuman',
            'description' => 'Kedai dan roastery kopi lokal di Bandung.',
            'contact_phone' => '0812-2045-7781',
            'city' => 'Bandung',
            'address' => 'Jl. Cihampelas No. 112, Bandung',
            'nib' => '9120304567812',
            'business_link' => 'instagram.com/kopinusantara.bdg',
            'verification_status' => VerificationStatus::Verified,
            'verified_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($umkmUser) {
            $browser->loginAs($umkmUser)
                ->visit('/umkm/profil')
                ->assertSee('Profil Usaha')
                ->assertInputValue('nama', 'Kopi Nusantara')
                ->assertInputValue('kota', 'Bandung')
                ->assertSee('Terverifikasi');
        });
    }

    public function test_tc_pbi1_003_ubah_kota_dan_kontak_mempertahankan_status_terverifikasi(): void
    {
        $umkmUser = User::factory()->umkm()->create();
        $verifiedAt = now()->subDays(2);

        $profile = $umkmUser->umkmProfile()->create([
            'business_name' => 'Kopi Nusantara',
            'business_field' => 'Kuliner & Minuman',
            'description' => 'Roastery kopi lokal.',
            'contact_phone' => '081234567890',
            'city' => 'Bandung',
            'address' => 'Jl. Contoh No. 1, Bandung',
            'nib' => '9120304567812',
            'business_link' => 'instagram.com/kopinusantara.bdg',
            'verification_status' => VerificationStatus::Verified,
            'verified_at' => $verifiedAt,
        ]);

        $this->browse(function (Browser $browser) use ($umkmUser, $profile) {
            $browser->loginAs($umkmUser)
                ->visit('/umkm/profil')
                ->type('kota', 'Cimahi')
                ->type('kontak', '+62 812-3456-7890')
                ->press('Simpan Perubahan')
                ->assertSee('Profil usaha berhasil disimpan.')
                ->assertInputValue('kota', 'Cimahi')
                ->assertInputValue('kontak', '+62 812-3456-7890')
                ->assertSee('Terverifikasi');

            $profile->refresh();
            $this->assertSame(VerificationStatus::Verified, $profile->verification_status);
        });
    }

    public function test_tc_pbi1_005_ubah_nama_usaha_mereset_status_verifikasi(): void
    {
        $umkmUser = User::factory()->umkm()->create();
        $profile = $umkmUser->umkmProfile()->create([
            'business_name' => 'Kopi Nusantara',
            'business_field' => 'Kuliner & Minuman',
            'description' => 'Roastery kopi lokal.',
            'contact_phone' => '081234567890',
            'city' => 'Bandung',
            'address' => 'Jl. Contoh No. 1, Bandung',
            'nib' => '9120304567812',
            'business_link' => 'instagram.com/kopinusantara.bdg',
            'verification_status' => VerificationStatus::Verified,
            'verified_at' => now()->subDay(),
        ]);

        $this->browse(function (Browser $browser) use ($umkmUser, $profile) {
            $browser->loginAs($umkmUser)
                ->visit('/umkm/profil')
                ->type('nama', 'Kopi Nusantara Baru')
                ->press('Simpan Perubahan')
                ->assertSee('Status verifikasi kembali menjadi Menunggu Pemeriksaan.')
                ->assertSee('Menunggu Pemeriksaan');

            $profile->refresh();
            $this->assertNull($profile->verified_at);
        });
    }

    public function test_tc_pbi1_012_nib_12_digit_ditolak(): void
    {
        $umkmUser = User::factory()->umkm()->create();

        $this->browse(function (Browser $browser) use ($umkmUser) {
            $browser->loginAs($umkmUser)
                ->visit('/umkm/profil')
                ->type('nama', 'Warung Uji')
                ->select('bidang', 'Kuliner & Minuman')
                ->type('deskripsi', 'Deskripsi usaha uji.')
                ->type('kontak', '081234567890')
                ->type('kota', 'Bandung')
                ->type('alamat', 'Jl. Uji No. 10')
                ->type('nib', '123456789012')
                ->press('Simpan Perubahan')
                ->assertSee('NIB harus terdiri dari 13 digit angka.');

            $this->assertDatabaseCount('umkm_profiles', 0);
        });
    }
}