<?php

namespace Tests\Feature\Umkm;

use App\Enums\VerificationStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsApplicationScenario;
use Tests\TestCase;

/** [PBI-01 / FR-02] Mengelola profil usaha dan melihat status verifikasi. */
class ProfilUsahaTest extends TestCase
{
    use RefreshDatabase, BuildsApplicationScenario;

    private function base(): array
    {
        return [
            'business_name' => 'Kopi Nusantara',
            'business_field' => 'Kuliner & Minuman',
            'description' => 'Roastery kopi lokal.',
            'contact_phone' => '081234567890',
            'city' => 'Bandung',
            'address' => 'Jl. Contoh No. 1, Bandung',
            'nib' => '9120304567812',
            'business_link' => 'instagram.com/kopinusantara',
        ];
    }

    private function verifiedUmkm(): User
    {
        return $this->makeUmkm(array_merge($this->base(), [
            'verification_status' => VerificationStatus::Verified,
            'verified_at' => now(),
        ]));
    }

    public function test_umkm_dapat_membuka_halaman_profil_usaha(): void
    {
        $umkm = $this->verifiedUmkm();

        $this->actingAs($umkm)->get(route('umkm.profil.edit'))
            ->assertOk()
            ->assertSee('Profil Usaha')
            ->assertSee('Terverifikasi');
    }

    public function test_kandidat_tidak_dapat_membuka_profil_usaha(): void
    {
        $this->actingAs($this->makeKandidat())->get(route('umkm.profil.edit'))->assertForbidden();
    }

    public function test_umkm_dapat_menyimpan_profil_valid(): void
    {
        $umkm = $this->verifiedUmkm();

        $this->actingAs($umkm)
            ->put(route('umkm.profil.update'), array_merge($this->base(), ['city' => 'Cimahi']))
            ->assertRedirect(route('umkm.profil.edit'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('umkm_profiles', ['user_id' => $umkm->id, 'city' => 'Cimahi']);
    }

    public function test_nib_dan_tautan_kosong_ditolak(): void
    {
        $umkm = $this->verifiedUmkm();

        $this->actingAs($umkm)
            ->put(route('umkm.profil.update'), array_merge($this->base(), ['nib' => '', 'business_link' => '']))
            ->assertSessionHasErrors(['nib', 'business_link']);
    }

    public function test_nib_harus_13_digit(): void
    {
        $umkm = $this->verifiedUmkm();

        $this->actingAs($umkm)
            ->put(route('umkm.profil.update'), array_merge($this->base(), ['nib' => '123']))
            ->assertSessionHasErrors('nib');
    }

    public function test_nama_usaha_wajib_diisi(): void
    {
        $umkm = $this->verifiedUmkm();

        $this->actingAs($umkm)
            ->put(route('umkm.profil.update'), array_merge($this->base(), ['business_name' => '']))
            ->assertSessionHasErrors('business_name');
    }

    public function test_ubah_nib_pada_profil_terverifikasi_mengembalikan_status_menunggu(): void
    {
        $umkm = $this->verifiedUmkm();

        $this->actingAs($umkm)
            ->put(route('umkm.profil.update'), array_merge($this->base(), ['nib' => '1111111111111']));

        $profile = $umkm->umkmProfile()->first();
        $this->assertSame(VerificationStatus::Pending, $profile->verification_status);
        $this->assertNull($profile->verified_at);
    }

    public function test_ubah_kontak_saja_tidak_mengubah_status_terverifikasi(): void
    {
        $umkm = $this->verifiedUmkm();

        $this->actingAs($umkm)
            ->put(route('umkm.profil.update'), array_merge($this->base(), ['contact_phone' => '085611112222']));

        $this->assertSame(VerificationStatus::Verified, $umkm->umkmProfile()->first()->verification_status);
    }

    public function test_alasan_penolakan_tampil_untuk_profil_ditolak(): void
    {
        $umkm = $this->makeUmkm(array_merge($this->base(), [
            'verification_status' => VerificationStatus::Rejected,
            'rejection_reason' => 'NIB tidak sesuai dengan nama usaha.',
        ]));

        $this->actingAs($umkm)->get(route('umkm.profil.edit'))
            ->assertSee('Ditolak')
            ->assertSee('NIB tidak sesuai dengan nama usaha.');
    }

    public function test_perbaikan_profil_ditolak_diajukan_ulang(): void
    {
        $umkm = $this->makeUmkm(array_merge($this->base(), [
            'verification_status' => VerificationStatus::Rejected,
            'rejection_reason' => 'NIB tidak sesuai.',
        ]));

        $this->actingAs($umkm)
            ->put(route('umkm.profil.update'), array_merge($this->base(), ['nib' => '2222222222222']));

        $profile = $umkm->umkmProfile()->first();
        $this->assertSame(VerificationStatus::Pending, $profile->verification_status);
        $this->assertNull($profile->rejection_reason);
    }
}