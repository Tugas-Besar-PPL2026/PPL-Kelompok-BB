<?php

namespace Tests\Feature\Kandidat\Admin;

use App\Enums\VerificationStatus;
use App\Models\UmkmProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsApplicationScenario;
use Tests\TestCase;

/** [PBI-08 / FR-05] Admin memverifikasi akun UMKM. */
class VerifikasiUmkmTest extends TestCase
{
    use RefreshDatabase, BuildsApplicationScenario;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function pendingUmkm(array $profile = []): User
    {
        $user = User::factory()->create([
            'name' => $profile['business_name'] ?? 'Laundry Bersih Kilat',
        ]);
        
        $umkmProfile = new UmkmProfile();
        $umkmProfile->user_id = $user->id;
        $umkmProfile->business_name = $profile['business_name'] ?? 'Laundry Bersih Kilat';
        $umkmProfile->business_field = $profile['business_field'] ?? 'Jasa';
        $umkmProfile->description = $profile['description'] ?? 'Laundry kilat dan bersih.';
        $umkmProfile->contact_phone = $profile['contact_phone'] ?? '08120015566702';
        $umkmProfile->city = $profile['city'] ?? 'Bandung';
        $umkmProfile->address = $profile['address'] ?? 'Jl. Dipati Ukur';
        $umkmProfile->nib = $profile['nib'] ?? '8120015566702';
        $umkmProfile->verification_status = $profile['verification_status'] ?? VerificationStatus::Pending;
        $umkmProfile->save();

        return $user;
    }

    public function test_admin_melihat_antrean_menunggu_beserta_detail(): void
    {
        $umkm = $this->pendingUmkm();

        $this->actingAs($this->admin())->get(route('admin.verifikasi.index'))
            ->assertOk()
            ->assertSee('Laundry Bersih Kilat')
            ->assertSee('8120015566702');
    }

    public function test_antrean_dapat_disaring_per_status(): void
    {
        $this->pendingUmkm(['business_name' => 'Usaha Menunggu']);
        
        $verifiedUser = User::factory()->create([
            'name' => 'Usaha Terverifikasi',
        ]);
        
        $verifiedProfile = new UmkmProfile();
        $verifiedProfile->user_id = $verifiedUser->id;
        $verifiedProfile->business_name = 'Usaha Terverifikasi';
        $verifiedProfile->business_field = 'Jasa';
        $verifiedProfile->description = 'Deskripsi usaha.';
        $verifiedProfile->contact_phone = '08123456789';
        $verifiedProfile->city = 'Bandung';
        $verifiedProfile->address = 'Jl. Merdeka';
        $verifiedProfile->verification_status = VerificationStatus::Verified;
        $verifiedProfile->save();

        $this->actingAs($this->admin())->get(route('admin.verifikasi.index', ['status' => 'verified']))
            ->assertSee('Usaha Terverifikasi')
            ->assertDontSee('Usaha Menunggu');
    }

    public function test_non_admin_tidak_dapat_mengakses(): void
    {
        $this->actingAs($this->makeUmkm())->get(route('admin.verifikasi.index'))->assertForbidden();
        $this->actingAs($this->makeKandidat())->get(route('admin.verifikasi.index'))->assertForbidden();
    }

    public function test_admin_menyetujui_umkm(): void
    {
        $umkm = $this->pendingUmkm();
        $profile = $umkm->umkmProfile()->first();

        $this->actingAs($this->admin())
            ->post(route('admin.verifikasi.decide', $profile->id), ['decision' => 'approve'])
            ->assertRedirect(route('admin.verifikasi.index'))
            ->assertSessionHas('success');

        $profile->refresh();
        $this->assertSame(VerificationStatus::Verified, $profile->verification_status);
        $this->assertNotNull($profile->verified_at);
        $this->assertDatabaseHas('notifications', ['user_id' => $umkm->id, 'type' => 'verification']);
    }

    public function test_penolakan_tanpa_alasan_gagal(): void
    {
        $profile = $this->pendingUmkm()->umkmProfile()->first();

        $this->actingAs($this->admin())
            ->post(route('admin.verifikasi.decide', $profile->id), ['decision' => 'reject', 'reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->assertSame(VerificationStatus::Pending, $profile->fresh()->verification_status);
    }

    public function test_penolakan_dengan_alasan_tersimpan_dan_terlihat_oleh_umkm(): void
    {
        $umkm = $this->pendingUmkm();
        $profile = $umkm->umkmProfile()->first();

        $this->actingAs($this->admin())->post(route('admin.verifikasi.decide', $profile->id), [
            'decision' => 'reject', 'reason' => 'NIB tidak sesuai dengan nama usaha.',
        ])->assertSessionHas('success');

        $profile->refresh();
        $this->assertSame(VerificationStatus::Rejected, $profile->verification_status);
        $this->assertSame('NIB tidak sesuai dengan nama usaha.', $profile->rejection_reason);

        $this->actingAs($umkm)->get(route('umkm.profil.edit'))
            ->assertSee('Ditolak')
            ->assertSee('NIB tidak sesuai dengan nama usaha.');
    }

    public function test_profil_yang_sudah_diputuskan_tidak_dapat_diputuskan_lagi(): void
    {
        $user = User::factory()->create();
        $profile = new UmkmProfile();
        $profile->user_id = $user->id;
        $profile->business_name = 'Usaha Selesai';
        $profile->business_field = 'Jasa';
        $profile->description = 'Deskripsi.';
        $profile->contact_phone = '0812345678';
        $profile->city = 'Bandung';
        $profile->address = 'Jl. Asia Afrika';
        $profile->verification_status = VerificationStatus::Verified;
        $profile->save();

        $this->actingAs($this->admin())
            ->post(route('admin.verifikasi.decide', $profile->id), ['decision' => 'reject', 'reason' => 'x'])
            ->assertSessionHas('error');

        $this->assertSame(VerificationStatus::Verified, $profile->fresh()->verification_status);
    }

    public function test_non_admin_tidak_dapat_memutuskan(): void
    {
        $profile = $this->pendingUmkm()->umkmProfile()->first();

        $this->actingAs($this->makeUmkm())
            ->post(route('admin.verifikasi.decide', $profile->id), ['decision' => 'approve'])
            ->assertForbidden();
    }
}