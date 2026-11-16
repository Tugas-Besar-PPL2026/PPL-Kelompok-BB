<?php

namespace Tests\Feature\Admin;

use App\Enums\ProjectStatus;
use App\Enums\VerificationStatus;
use App\Models\Notification;
use App\Models\UmkmProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\Concerns\BuildsApplicationScenario;
use Tests\Concerns\StubsLoginRoute;
use Tests\TestCase;

/**
 * [PBI-08 / FR-05] Admin memverifikasi akun UMKM.
 * Kode skenario (F08-xx) dipakai di laporan pengujian.
 */
class VerifikasiUmkmTest extends TestCase
{
    use RefreshDatabase, BuildsApplicationScenario, StubsLoginRoute;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function umkmWith(VerificationStatus $status, array $profile = []): User
    {
        return $this->makeUmkm($profile + [
            'business_name' => 'Laundry Bersih Kilat',
            'nib' => '8120015566702',
            'verification_status' => $status,
            'verified_at' => $status === VerificationStatus::Verified ? now() : null,
            'rejection_reason' => $status === VerificationStatus::Rejected ? 'Data belum sesuai.' : null,
        ]);
    }

    private function pendingUmkm(array $profile = []): User
    {
        return $this->umkmWith(VerificationStatus::Pending, $profile);
    }

    private function profileOf(User $umkm): UmkmProfile
    {
        return $umkm->umkmProfile()->first();
    }

    // ---------------------------------------------------------------- Antrean & detail (kriteria 1, 2)

    /** F08-01 */
    public function test_admin_melihat_antrean_menunggu_beserta_detail_lengkap(): void
    {
        $umkm = $this->pendingUmkm([
            'business_field' => 'Jasa Laundry',
            'description' => 'Laundry kiloan antar-jemput.',
            'contact_phone' => '085722119034',
            'city' => 'Bandung',
            'address' => 'Jl. Cikutra No. 45',
            'business_link' => 'instagram.com/laundrykilat',
        ]);

        $this->actingAs($this->admin())->get(route('admin.verifikasi.index'))
            ->assertOk()
            ->assertSee('Verifikasi Akun UMKM')
            ->assertSee('Laundry Bersih Kilat')
            ->assertSee($umkm->name)
            ->assertSee($umkm->email)
            ->assertSee('Jasa Laundry')
            ->assertSee('Laundry kiloan antar-jemput.')
            ->assertSee('085722119034')
            ->assertSee('Bandung')
            ->assertSee('Jl. Cikutra No. 45')
            ->assertSee('8120015566702')
            ->assertSee('instagram.com/laundrykilat')
            ->assertSee('Menunggu Pemeriksaan');
    }

    /** F08-02 */
    public function test_tab_antrean_menampilkan_jumlah_per_status(): void
    {
        $this->pendingUmkm(['business_name' => 'Menunggu A']);
        $this->pendingUmkm(['business_name' => 'Menunggu B']);
        $this->umkmWith(VerificationStatus::Verified, ['business_name' => 'Verif A']);
        $this->umkmWith(VerificationStatus::Rejected, ['business_name' => 'Tolak A']);

        $this->actingAs($this->admin())->get(route('admin.verifikasi.index'))
            ->assertOk()
            ->assertSee('Menunggu Pemeriksaan <span>2</span>', false)
            ->assertSee('Terverifikasi <span>1</span>', false)
            ->assertSee('Ditolak <span>1</span>', false);
    }

    /** F08-03 */
    public function test_antrean_dapat_disaring_per_status(): void
    {
        $this->pendingUmkm(['business_name' => 'Usaha Menunggu']);
        $this->umkmWith(VerificationStatus::Verified, ['business_name' => 'Usaha Terverifikasi']);
        $this->umkmWith(VerificationStatus::Rejected, ['business_name' => 'Usaha Ditolak']);
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.verifikasi.index', ['status' => 'verified']))
            ->assertSee('Usaha Terverifikasi')->assertDontSee('Usaha Menunggu')->assertDontSee('Usaha Ditolak');

        $this->actingAs($admin)->get(route('admin.verifikasi.index', ['status' => 'rejected']))
            ->assertSee('Usaha Ditolak')->assertDontSee('Usaha Menunggu')->assertDontSee('Usaha Terverifikasi');

        $this->actingAs($admin)->get(route('admin.verifikasi.index', ['status' => 'pending']))
            ->assertSee('Usaha Menunggu')->assertDontSee('Usaha Ditolak')->assertDontSee('Usaha Terverifikasi');
    }

    /** F08-04 */
    public function test_antrean_ditolak_menampilkan_alasan_penolakan_dan_tanpa_form_keputusan(): void
    {
        $this->umkmWith(VerificationStatus::Rejected, ['rejection_reason' => 'NIB tidak sesuai dengan nama usaha.']);

        $this->actingAs($this->admin())->get(route('admin.verifikasi.index', ['status' => 'rejected']))
            ->assertOk()
            ->assertSee('Alasan penolakan')
            ->assertSee('NIB tidak sesuai dengan nama usaha.')
            ->assertDontSee('Keputusan Verifikasi');
    }

    /** F08-05 (boundary: nilai status liar kembali ke antrean Menunggu) */
    public function test_status_tidak_valid_kembali_ke_antrean_menunggu(): void
    {
        $this->pendingUmkm(['business_name' => 'Usaha Menunggu']);
        $this->umkmWith(VerificationStatus::Verified, ['business_name' => 'Usaha Terverifikasi']);
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.verifikasi.index', ['status' => 'bukan-status']))
            ->assertOk()->assertSee('Usaha Menunggu')->assertDontSee('Usaha Terverifikasi');

        $this->actingAs($admin)->get('/admin/verifikasi?status[]=verified')
            ->assertOk()->assertSee('Usaha Menunggu');
    }

    /** F08-06 */
    public function test_admin_memilih_usaha_tertentu_dari_antrean(): void
    {
        $this->pendingUmkm(['business_name' => 'Usaha Pertama', 'nib' => '1111111111111']);
        $kedua = $this->profileOf($this->pendingUmkm(['business_name' => 'Usaha Kedua', 'nib' => '2222222222222']));

        $this->actingAs($this->admin())
            ->get(route('admin.verifikasi.index', ['status' => 'pending', 'usaha' => $kedua->id]))
            ->assertOk()
            ->assertSee('2222222222222')
            ->assertDontSee('1111111111111');
    }

    /** F08-07 */
    public function test_antrean_tanpa_pilihan_menampilkan_usaha_pertama(): void
    {
        $this->pendingUmkm(['business_name' => 'Usaha Pertama', 'nib' => '1111111111111']);
        $this->pendingUmkm(['business_name' => 'Usaha Kedua', 'nib' => '2222222222222']);

        $this->actingAs($this->admin())->get(route('admin.verifikasi.index'))
            ->assertOk()->assertSee('1111111111111')->assertDontSee('2222222222222');
    }

    /** F08-08 */
    public function test_parameter_usaha_tanpa_status_membuka_antrean_sesuai_status_usaha(): void
    {
        $this->pendingUmkm(['business_name' => 'Usaha Menunggu', 'nib' => '1111111111111']);
        $verified = $this->profileOf($this->umkmWith(VerificationStatus::Verified, ['business_name' => 'Usaha Terverifikasi', 'nib' => '3333333333333']));

        $this->actingAs($this->admin())->get(route('admin.verifikasi.index', ['usaha' => $verified->id]))
            ->assertOk()
            ->assertSee('3333333333333')
            ->assertDontSee('1111111111111');
    }

    /** F08-09 (boundary / negative: id tidak ada, bukan angka, array) */
    public function test_parameter_usaha_tidak_valid_tidak_menyebabkan_galat(): void
    {
        $this->pendingUmkm(['business_name' => 'Usaha Menunggu']);
        $admin = $this->admin();

        foreach (['/admin/verifikasi?usaha=999999', '/admin/verifikasi?usaha=abc', '/admin/verifikasi?usaha[]=1', '/admin/verifikasi?usaha=99999999999999999999999'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk()->assertSee('Usaha Menunggu');
        }
    }

    /** F08-10 */
    public function test_antrean_kosong_menampilkan_pesan(): void
    {
        $this->actingAs($this->admin())->get(route('admin.verifikasi.index'))
            ->assertOk()
            ->assertSee('Tidak ada usaha pada antrean ini.')
            ->assertSee('Pilih usaha dari antrean untuk melihat detail.');
    }

    /** F08-11 */
    public function test_tautan_detail_usaha_mengarah_ke_antrean_yang_sesuai(): void
    {
        $verified = $this->profileOf($this->umkmWith(VerificationStatus::Verified));

        $this->actingAs($this->admin())->get(route('admin.verifikasi.show', $verified->id))
            ->assertRedirect(route('admin.verifikasi.index', ['status' => 'verified', 'usaha' => $verified->id]));
    }

    /** F08-12 */
    public function test_data_usaha_ditampilkan_aman_dari_html(): void
    {
        $this->pendingUmkm(['business_name' => '<script>alert(1)</script>']);

        $this->actingAs($this->admin())->get(route('admin.verifikasi.index'))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    /** F08-13 */
    public function test_menu_verifikasi_umkm_ada_di_sidebar_admin(): void
    {
        $this->actingAs($this->admin())->get(route('admin.verifikasi.index'))
            ->assertSee('Verifikasi UMKM')
            ->assertSee(route('admin.verifikasi.index'), false);
    }

    // ---------------------------------------------------------------- Keputusan (kriteria 3, 4, 5)

    /** F08-14 */
    public function test_admin_menyetujui_umkm(): void
    {
        $umkm = $this->pendingUmkm();
        $profile = $this->profileOf($umkm);

        $this->actingAs($this->admin())
            ->post(route('admin.verifikasi.decide', $profile->id), ['decision' => 'approve'])
            ->assertRedirect(route('admin.verifikasi.index'))
            ->assertSessionHas('success', 'Akun UMKM berhasil diverifikasi.');

        $profile->refresh();
        $this->assertSame(VerificationStatus::Verified, $profile->verification_status);
        $this->assertNotNull($profile->verified_at);
        $this->assertNull($profile->rejection_reason);
    }

    /** F08-15 */
    public function test_persetujuan_mengabaikan_alasan_yang_terkirim(): void
    {
        $profile = $this->profileOf($this->pendingUmkm());

        $this->actingAs($this->admin())
            ->post(route('admin.verifikasi.decide', $profile->id), ['decision' => 'approve', 'reason' => 'abaikan saya']);

        $this->assertNull($profile->fresh()->rejection_reason);
    }

    /** F08-16 */
    public function test_penolakan_dengan_alasan_tersimpan(): void
    {
        $profile = $this->profileOf($this->pendingUmkm());

        $this->actingAs($this->admin())->post(route('admin.verifikasi.decide', $profile->id), [
            'decision' => 'reject', 'reason' => '  NIB tidak sesuai dengan nama usaha.  ',
        ])->assertRedirect(route('admin.verifikasi.index'))
            ->assertSessionHas('success', 'Verifikasi UMKM ditolak.');

        $profile->refresh();
        $this->assertSame(VerificationStatus::Rejected, $profile->verification_status);
        $this->assertSame('NIB tidak sesuai dengan nama usaha.', $profile->rejection_reason);
        $this->assertNull($profile->verified_at);
    }

    /** F08-17 (negative: alasan kosong / spasi / tidak dikirim) */
    public function test_penolakan_tanpa_alasan_gagal(): void
    {
        $profile = $this->profileOf($this->pendingUmkm());
        $admin = $this->admin();

        foreach ([['decision' => 'reject', 'reason' => ''], ['decision' => 'reject', 'reason' => '    '], ['decision' => 'reject']] as $payload) {
            $this->actingAs($admin)
                ->post(route('admin.verifikasi.decide', $profile->id), $payload)
                ->assertSessionHasErrors(['reason' => 'Alasan penolakan wajib diisi.']);
        }

        $this->assertSame(VerificationStatus::Pending, $profile->fresh()->verification_status);
        $this->assertSame(0, Notification::count());
    }

    /** F08-18 (boundary: 500 diterima, 501 ditolak) */
    public function test_batas_panjang_alasan_penolakan(): void
    {
        $admin = $this->admin();

        $ditolak = $this->profileOf($this->pendingUmkm());
        $this->actingAs($admin)
            ->post(route('admin.verifikasi.decide', $ditolak->id), ['decision' => 'reject', 'reason' => str_repeat('a', 501)])
            ->assertSessionHasErrors(['reason' => 'Alasan penolakan maksimal 500 karakter.']);
        $this->assertSame(VerificationStatus::Pending, $ditolak->fresh()->verification_status);

        $diterima = $this->profileOf($this->pendingUmkm());
        $this->actingAs($admin)
            ->post(route('admin.verifikasi.decide', $diterima->id), ['decision' => 'reject', 'reason' => str_repeat('a', 500)])
            ->assertSessionHasNoErrors();
        $this->assertSame(VerificationStatus::Rejected, $diterima->fresh()->verification_status);
        $this->assertSame(500, mb_strlen($diterima->fresh()->rejection_reason));
    }

    /** F08-19 (negative: keputusan hilang / tidak dikenal) */
    public function test_keputusan_tidak_valid_ditolak(): void
    {
        $profile = $this->profileOf($this->pendingUmkm());
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.verifikasi.decide', $profile->id), [])
            ->assertSessionHasErrors(['decision' => 'Pilih keputusan verifikasi.']);

        $this->actingAs($admin)->post(route('admin.verifikasi.decide', $profile->id), ['decision' => 'hapus'])
            ->assertSessionHasErrors(['decision' => 'Keputusan tidak valid.']);

        $this->assertSame(VerificationStatus::Pending, $profile->fresh()->verification_status);
    }

    /** F08-20 */
    public function test_profil_yang_sudah_diputuskan_tidak_dapat_diputuskan_lagi(): void
    {
        $admin = $this->admin();

        foreach ([VerificationStatus::Verified, VerificationStatus::Rejected] as $status) {
            $profile = $this->profileOf($this->umkmWith($status));

            $this->actingAs($admin)
                ->post(route('admin.verifikasi.decide', $profile->id), ['decision' => 'reject', 'reason' => 'x'])
                ->assertRedirect(route('admin.verifikasi.index'))
                ->assertSessionHas('error', 'Profil usaha ini sudah diputuskan sebelumnya.');

            $this->assertSame($status, $profile->fresh()->verification_status);
        }

        $this->assertSame(0, Notification::count());
    }

    /** F08-21 (keputusan dikirim dua kali: hanya yang pertama berlaku, notifikasi tidak ganda) */
    public function test_keputusan_ganda_hanya_diterapkan_sekali(): void
    {
        $umkm = $this->pendingUmkm();
        $profile = $this->profileOf($umkm);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.verifikasi.decide', $profile->id), ['decision' => 'approve']);
        $this->actingAs($admin)->post(route('admin.verifikasi.decide', $profile->id), ['decision' => 'reject', 'reason' => 'terlambat'])
            ->assertSessionHas('error');

        $this->assertSame(VerificationStatus::Verified, $profile->fresh()->verification_status);
        $this->assertSame(1, Notification::where('user_id', $umkm->id)->count());
    }

    /** F08-22 (negative: profil tidak ada / id bukan angka) */
    public function test_keputusan_untuk_profil_yang_tidak_ada_menghasilkan_404(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.verifikasi.decide', 999999), ['decision' => 'approve'])->assertNotFound();
        $this->actingAs($admin)->post('/admin/verifikasi/abc/keputusan', ['decision' => 'approve'])->assertNotFound();
    }

    // ---------------------------------------------------------------- Notifikasi & dampak ke UMKM (kriteria 4, 5)

    /** F08-23 */
    public function test_umkm_menerima_notifikasi_saat_disetujui(): void
    {
        $umkm = $this->pendingUmkm();
        $profile = $this->profileOf($umkm);

        $this->actingAs($this->admin())->post(route('admin.verifikasi.decide', $profile->id), ['decision' => 'approve']);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $umkm->id,
            'type' => 'verification',
            'related_type' => 'umkm_profile',
            'related_id' => $profile->id,
            'is_read' => false,
        ]);
        $this->assertStringContainsString('terverifikasi', Notification::where('user_id', $umkm->id)->value('message'));

        $this->actingAs($umkm)->get(route('umkm.profil.edit'))
            ->assertOk()
            ->assertSee('<span class="cnt">1</span>', false)
            ->assertSee('Terverifikasi');
    }

    /** F08-24 */
    public function test_umkm_menerima_notifikasi_saat_ditolak(): void
    {
        $umkm = $this->pendingUmkm();
        $profile = $this->profileOf($umkm);

        $this->actingAs($this->admin())->post(route('admin.verifikasi.decide', $profile->id), [
            'decision' => 'reject', 'reason' => 'Alamat tidak lengkap.',
        ]);

        $notification = Notification::where('user_id', $umkm->id)->sole();
        $this->assertSame('verification', $notification->type);
        $this->assertStringContainsString('ditolak', $notification->message);
        $this->assertLessThanOrEqual(255, mb_strlen($notification->message));
    }

    /** F08-25 */
    public function test_notifikasi_hanya_untuk_pemilik_usaha_yang_diputuskan(): void
    {
        $target = $this->pendingUmkm(['business_name' => 'Target']);
        $lain = $this->pendingUmkm(['business_name' => 'Lain']);

        $this->actingAs($this->admin())
            ->post(route('admin.verifikasi.decide', $this->profileOf($target)->id), ['decision' => 'approve']);

        $this->assertSame(1, Notification::where('user_id', $target->id)->count());
        $this->assertSame(0, Notification::where('user_id', $lain->id)->count());
        $this->assertSame(VerificationStatus::Pending, $this->profileOf($lain)->verification_status);
    }

    /** F08-26 (kriteria 4: alasan terlihat oleh UMKM di Profil Usaha / PBI-01) */
    public function test_penolakan_terlihat_oleh_umkm_di_profil_usaha(): void
    {
        $umkm = $this->pendingUmkm();
        $profile = $this->profileOf($umkm);

        $this->actingAs($this->admin())->post(route('admin.verifikasi.decide', $profile->id), [
            'decision' => 'reject', 'reason' => 'NIB tidak sesuai dengan nama usaha.',
        ])->assertSessionHas('success');

        $this->actingAs($umkm)->get(route('umkm.profil.edit'))
            ->assertOk()
            ->assertSee('Ditolak')
            ->assertSee('Alasan penolakan')
            ->assertSee('NIB tidak sesuai dengan nama usaha.');
    }

    // ---------------------------------------------------------------- Otorisasi (kriteria 6)

    /** F08-27 */
    public function test_non_admin_tidak_dapat_mengakses_antrean(): void
    {
        $profile = $this->profileOf($this->pendingUmkm());

        $this->actingAs($this->makeUmkm())->get(route('admin.verifikasi.index'))->assertForbidden();
        $this->actingAs($this->makeKandidat())->get(route('admin.verifikasi.index'))->assertForbidden();
        $this->actingAs($this->makeUmkm())->get(route('admin.verifikasi.show', $profile->id))->assertForbidden();
        $this->actingAs($this->makeKandidat())->get(route('admin.verifikasi.show', $profile->id))->assertForbidden();
    }

    /** F08-28 */
    public function test_non_admin_tidak_dapat_memutuskan_dan_data_tidak_berubah(): void
    {
        $pemilik = $this->pendingUmkm();
        $profile = $this->profileOf($pemilik);

        // UMKM lain, pemilik usaha itu sendiri, dan kandidat.
        foreach ([$this->makeUmkm(), $pemilik, $this->makeKandidat()] as $user) {
            $this->actingAs($user)
                ->post(route('admin.verifikasi.decide', $profile->id), ['decision' => 'approve'])
                ->assertForbidden();
        }

        $this->assertSame(VerificationStatus::Pending, $profile->fresh()->verification_status);
        $this->assertSame(0, Notification::count());
    }

    /** F08-29 */
    public function test_tamu_dialihkan_ke_halaman_login(): void
    {
        $this->stubLoginRoute();
        $profile = $this->profileOf($this->pendingUmkm());

        $this->get(route('admin.verifikasi.index'))->assertRedirect(route('login'));
        $this->post(route('admin.verifikasi.decide', $profile->id), ['decision' => 'approve'])->assertRedirect(route('login'));

        $this->assertSame(VerificationStatus::Pending, $profile->fresh()->verification_status);
    }

    // ---------------------------------------------------------------- Kriteria 7: pengajuan proyek

    /** F08-30 */
    public function test_hanya_umkm_terverifikasi_pemilik_proyek_yang_boleh_mengajukan_proyek(): void
    {
        $buatProyek = fn (User $umkm) => $this->makeProject(['status' => ProjectStatus::Draft, 'published_at' => null], $umkm->umkmProfile);

        $verified = $this->umkmWith(VerificationStatus::Verified);
        $pending = $this->umkmWith(VerificationStatus::Pending);
        $rejected = $this->umkmWith(VerificationStatus::Rejected);

        $this->assertTrue(Gate::forUser($verified)->allows('submit', $buatProyek($verified)));
        $this->assertFalse(Gate::forUser($pending)->allows('submit', $buatProyek($pending)));
        $this->assertFalse(Gate::forUser($rejected)->allows('submit', $buatProyek($rejected)));

        // Bukan pemilik, kandidat, dan admin tidak boleh.
        $milikVerified = $buatProyek($verified);
        $this->assertFalse(Gate::forUser($this->umkmWith(VerificationStatus::Verified))->allows('submit', $milikVerified));
        $this->assertFalse(Gate::forUser($this->makeKandidat())->allows('submit', $milikVerified));
        $this->assertFalse(Gate::forUser($this->admin())->allows('submit', $milikVerified));
    }

    /** F08-31 (regresi: menyimpan draf tetap boleh walau belum terverifikasi) */
    public function test_umkm_belum_terverifikasi_tetap_dapat_membuka_form_draf(): void
    {
        $pending = $this->pendingUmkm();

        $this->actingAs($pending)->get(route('umkm.proyek.create'))
            ->assertOk()
            ->assertSee('Pengajuan ke admin baru bisa dilakukan setelah akun UMKM terverifikasi.');
    }

    /** F08-32 (setelah disetujui admin, gerbang pengajuan terbuka) */
    public function test_persetujuan_admin_membuka_gerbang_pengajuan_proyek(): void
    {
        $umkm = $this->pendingUmkm();
        $project = $this->makeProject(['status' => ProjectStatus::Draft, 'published_at' => null], $umkm->umkmProfile);

        $this->assertFalse(Gate::forUser($umkm)->allows('submit', $project));

        $this->actingAs($this->admin())
            ->post(route('admin.verifikasi.decide', $this->profileOf($umkm)->id), ['decision' => 'approve']);

        $this->assertTrue(Gate::forUser($umkm->fresh())->allows('submit', $project->fresh()));
    }
}
