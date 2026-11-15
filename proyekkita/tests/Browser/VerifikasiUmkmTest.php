<?php

namespace Tests\Browser;

use App\Enums\VerificationStatus;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Concerns\BuildsApplicationScenario;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

/** [PBI-08 / FR-05] Verifikasi akun UMKM oleh admin melalui browser. */
class VerifikasiUmkmTest extends DuskTestCase
{
    use BuildsApplicationScenario, UsesDuskTestDatabase;

    private function pendingProfile()
    {
        return $this->makeUmkm([
            'business_name' => 'Laundry Bersih Kilat',
            'nib' => '8120015566702',
            'verification_status' => VerificationStatus::Pending,
        ])->umkmProfile()->first();
    }

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
}
