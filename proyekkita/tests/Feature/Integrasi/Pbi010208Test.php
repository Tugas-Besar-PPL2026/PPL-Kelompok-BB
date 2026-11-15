<?php

namespace Tests\Feature\Integrasi;

use App\Enums\VerificationStatus;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsApplicationScenario;
use Tests\TestCase;

/**
 * Uji kompatibilitas PBI-01 (profil usaha), PBI-02 (profil kandidat), dan PBI-08 (verifikasi UMKM)
 * setelah digabung. Kode skenario: INT-xx.
 */
class Pbi010208Test extends TestCase
{
    use RefreshDatabase, BuildsApplicationScenario;

    private function profilUsaha(array $o = []): array
    {
        return array_merge([
            'business_name' => 'Kopi Nusantara',
            'business_field' => 'Kuliner & Minuman',
            'description' => 'Roastery kopi lokal.',
            'contact_phone' => '081234567890',
            'city' => 'Bandung',
            'address' => 'Jl. Contoh No. 1, Bandung',
            'nib' => '9120304567812',
            'business_link' => '',
        ], $o);
    }

    /** INT-01: setiap route ketiga PBI menunjuk ke controller dan method yang benar-benar ada. */
    public function test_semua_route_pbi_menunjuk_ke_controller_dan_method_yang_ada(): void
    {
        $names = [
            'umkm.profil.edit', 'umkm.profil.update',
            'kandidat.profil.datadiri', 'kandidat.profil.datadiri.update',
            'admin.verifikasi.index', 'admin.verifikasi.show', 'admin.verifikasi.decide',
        ];

        foreach ($names as $name) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, "Route {$name} tidak terdaftar.");

            [$class, $method] = explode('@', $route->getActionName());
            $this->assertTrue(class_exists($class), "Controller {$class} (route {$name}) tidak ditemukan.");
            $this->assertTrue(method_exists($class, $method), "Method {$class}::{$method} (route {$name}) tidak ada.");
        }
    }

    /** INT-02: tidak ada nama route ganda dari PBI lain yang menimpa route PBI-01/02/08. */
    public function test_route_lama_profil_usaha_sudah_tidak_ada(): void
    {
        $this->assertFalse(Route::has('profil-usaha'));
        $this->assertFalse(Route::has('umkm.profil-usaha'));
        $this->assertTrue(Route::has('kandidat.profil.edit'), 'Route PBI-03 (keterampilan) harus tetap ada.');
        $this->assertTrue(Route::has('kandidat.profil.portofolio'), 'Route PBI-04 (portofolio) harus tetap ada.');
    }

    /** INT-03: alur utuh UMKM mengisi profil -> admin menyetujui -> UMKM mengubah data -> diverifikasi ulang. */
    public function test_alur_utuh_pengajuan_verifikasi_ulang_dan_penolakan(): void
    {
        // fresh(): relasi umkmProfile pada objek pengguna tidak boleh ikut ter-cache antar permintaan.
        $umkm = User::factory()->umkm()->create();
        $admin = User::factory()->admin()->create();

        // PBI-01: profil dibuat pertama kali -> Menunggu Pemeriksaan.
        $this->actingAs($umkm->fresh())->put(route('umkm.profil.update'), $this->profilUsaha())
            ->assertRedirect(route('umkm.profil.edit'));
        $profile = $umkm->umkmProfile()->first();
        $this->assertSame(VerificationStatus::Pending, $profile->verification_status);

        // PBI-08: muncul di antrean admin, lalu disetujui.
        $this->actingAs($admin)->get(route('admin.verifikasi.index'))->assertSee('Kopi Nusantara');
        $this->actingAs($admin)->post(route('admin.verifikasi.decide', $profile->id), ['decision' => 'approve'])
            ->assertSessionHas('success');
        $this->assertSame(VerificationStatus::Verified, $profile->fresh()->verification_status);

        // PBI-01: ubah kontak saja -> tetap Terverifikasi dan tidak kembali ke antrean.
        $this->actingAs($umkm->fresh())->put(route('umkm.profil.update'), $this->profilUsaha(['contact_phone' => '085611112222']));
        $this->assertSame(VerificationStatus::Verified, $profile->fresh()->verification_status);
        $this->actingAs($admin)->get(route('admin.verifikasi.index'))->assertSee('Tidak ada usaha pada antrean ini.');

        // PBI-01: ubah nama usaha -> kembali Menunggu, muncul lagi di antrean admin.
        $this->actingAs($umkm->fresh())->put(route('umkm.profil.update'), $this->profilUsaha(['business_name' => 'Kopi Nusantara Baru']))
            ->assertSessionHas('success', 'Profil usaha berhasil disimpan. Status verifikasi kembali menjadi Menunggu Pemeriksaan.');
        $this->assertSame(VerificationStatus::Pending, $profile->fresh()->verification_status);
        $this->assertNull($profile->fresh()->verified_at);
        $this->actingAs($admin)->get(route('admin.verifikasi.index'))->assertSee('Kopi Nusantara Baru');

        // PBI-08: ditolak dengan alasan -> terlihat di PBI-01.
        $this->actingAs($admin)->post(route('admin.verifikasi.decide', $profile->id), ['decision' => 'reject', 'reason' => 'NIB tidak ditemukan di OSS.']);
        $this->actingAs($umkm->fresh())->get(route('umkm.profil.edit'))->assertSee('Ditolak')->assertSee('NIB tidak ditemukan di OSS.');

        // PBI-01: diperbaiki -> diajukan ulang, alasan lama dihapus, kembali ke antrean admin.
        $this->actingAs($umkm->fresh())->put(route('umkm.profil.update'), $this->profilUsaha(['business_name' => 'Kopi Nusantara Baru', 'nib' => '1234567890123']));
        $profile->refresh();
        $this->assertSame(VerificationStatus::Pending, $profile->verification_status);
        $this->assertNull($profile->rejection_reason);
        $this->actingAs($admin)->get(route('admin.verifikasi.index'))->assertSee('1234567890123');

        // Notifikasi: satu untuk persetujuan, satu untuk penolakan.
        $this->assertSame(2, Notification::where('user_id', $umkm->id)->where('type', 'verification')->count());
    }

    /** INT-04: penolakan PBI-08 tidak merusak data profil usaha PBI-01 selain status. */
    public function test_keputusan_admin_tidak_mengubah_data_profil_usaha(): void
    {
        $umkm = $this->makeUmkm($this->profilUsaha(['verification_status' => VerificationStatus::Pending]));
        $before = $umkm->umkmProfile()->first()->only(['business_name', 'business_field', 'description', 'contact_phone', 'city', 'address', 'nib', 'business_link']);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.verifikasi.decide', $umkm->umkmProfile()->first()->id), ['decision' => 'reject', 'reason' => 'Uji.']);

        $after = $umkm->umkmProfile()->first()->only(array_keys($before));
        $this->assertSame($before, $after);
    }

    /** INT-05: kandidat menyimpan data diri; halaman PBI-03/PBI-04 dan data usaha UMKM tidak terpengaruh. */
    public function test_penyimpanan_data_diri_tidak_mengganggu_halaman_pbi_lain(): void
    {
        Storage::fake('public');
        $kandidat = $this->makeKandidat();
        $umkm = $this->makeUmkm();
        $namaUsaha = $umkm->umkmProfile()->first()->business_name;

        $this->actingAs($kandidat)->put(route('kandidat.profil.datadiri.update'), [
            'full_name' => 'Nama Baru', 'city' => 'Cimahi', 'headline' => 'Headline baru', 'university' => 'ITB',
            'major' => 'Informatika', 'summary' => 'Ringkasan baru.',
        ])->assertSessionHas('success');

        $this->actingAs($kandidat)->get(route('kandidat.profil.edit'))->assertOk();
        $this->actingAs($kandidat)->get(route('kandidat.profil.portofolio'))->assertOk();
        $this->actingAs($kandidat)->get(route('kandidat.profil.datadiri'))->assertOk()->assertSee('Nama Baru');
        $this->assertSame($namaUsaha, $umkm->umkmProfile()->first()->business_name);
    }

    /** INT-06: halaman ketiga PBI dapat dibuka oleh perannya masing-masing dan ditolak untuk peran lain. */
    public function test_matriks_akses_peran_untuk_halaman_ketiga_pbi(): void
    {
        $admin = User::factory()->admin()->create();
        $umkm = $this->makeUmkm();
        $kandidat = $this->makeKandidat();

        $matrix = [
            'umkm.profil.edit' => [$umkm, [$admin, $kandidat]],
            'kandidat.profil.datadiri' => [$kandidat, [$admin, $umkm]],
            'admin.verifikasi.index' => [$admin, [$umkm, $kandidat]],
        ];

        foreach ($matrix as $routeName => [$allowed, $denied]) {
            $this->actingAs($allowed)->get(route($routeName))->assertOk();
            foreach ($denied as $user) {
                $this->actingAs($user)->get(route($routeName))->assertForbidden();
            }
        }
    }
}
