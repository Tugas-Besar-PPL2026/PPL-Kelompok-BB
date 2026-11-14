<?php

namespace Tests\Feature\Kandidat;

use App\Enums\ApplicationStatus;
use App\Enums\ProjectStatus;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\Project;
use App\Models\User;
use App\Services\ApplicationService;
use App\Services\CandidateApplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Tests\Concerns\BuildsApplicationScenario;
use Tests\Concerns\UsesInMemoryDatabase;
use Tests\TestCase;

/** [PBI-17 / FR-10] Halaman Lamaran Saya: daftar, status, catatan seleksi, riwayat, dan filter. */
class LamaranSayaTest extends TestCase
{
    use BuildsApplicationScenario;
    use RefreshDatabase, UsesInMemoryDatabase;

    private function halaman(User $user, array $query = [])
    {
        return $this->actingAs($user)->get(route('kandidat.lamaran.index', $query));
    }

    /**
     * Membuat lamaran dengan status, tanggal, catatan, dan riwayat tertentu.
     * $histories: daftar [status, changed_at, note]. Bila null, dibuat satu entri awal Menunggu Keputusan (seperti PBI-16).
     */
    private function makeApplication(User $user, array $attrs = [], ?array $histories = null, ?Project $project = null, string $umkm = 'Kopi Nusantara'): Application
    {
        $project ??= $this->makeProject(
            ['title' => $attrs['title'] ?? 'Proyek '.uniqid()],
            $this->makeUmkm(['business_name' => $umkm])->umkmProfile,
        );
        $createdAt = $attrs['created_at'] ?? '2026-11-04 10:00:00';

        $application = new Application();
        $application->forceFill([
            'project_id' => $project->id,
            'candidate_profile_id' => $user->candidateProfile->id,
            'message' => 'Pesan lamaran uji yang cukup panjang untuk memenuhi batas minimal lima puluh karakter.',
            'status' => $attrs['status'] ?? ApplicationStatus::Pending,
            'selection_note' => $attrs['selection_note'] ?? null,
            'created_at' => $createdAt,
        ])->save();

        $histories ??= [[ApplicationStatus::Pending, $createdAt, null]];
        foreach ($histories as [$status, $changedAt, $note]) {
            ApplicationStatusHistory::create([
                'application_id' => $application->id, 'status' => $status, 'note' => $note, 'changed_at' => $changedAt,
            ]);
        }

        return $application;
    }

    // ---------- Daftar, kepemilikan, dan informasi proyek ----------

    public function test_kandidat_melihat_lamarannya_beserta_informasi_proyek_dan_umkm(): void
    {
        $user = $this->makeKandidat();
        $this->makeApplication($user, ['title' => 'Redesain Katalog Uji'], umkm: 'Kedai Teh Sinar');

        $this->halaman($user)
            ->assertOk()
            ->assertSee('Lamaran Saya')
            ->assertSee('Redesain Katalog Uji')
            ->assertSee('Kedai Teh Sinar')
            ->assertSee('Menunggu Keputusan')
            ->assertSee('Dilamar 4 Nov 2026')
            ->assertSee('Lamaran hanya dapat dibatalkan selama berstatus');
    }

    public function test_lamaran_kandidat_lain_tidak_tampil_dan_tidak_dihitung(): void
    {
        $saya = $this->makeKandidat();
        $lain = $this->makeKandidat();
        $this->makeApplication($saya, ['title' => 'Lamaran Milik Saya']);
        $this->makeApplication($lain, ['title' => 'Lamaran Milik Orang Lain', 'selection_note' => 'Catatan rahasia kandidat lain']);

        $this->halaman($saya)
            ->assertOk()
            ->assertSee('Lamaran Milik Saya')
            ->assertDontSee('Lamaran Milik Orang Lain')
            ->assertDontSee('Catatan rahasia kandidat lain');

        $this->assertSame(1, app(CandidateApplicationService::class)->counts($saya->candidateProfile)['all']);
    }

    public function test_semua_status_tampil_dengan_labelnya(): void
    {
        $user = $this->makeKandidat();
        foreach (ApplicationStatus::cases() as $i => $status) {
            $this->makeApplication($user, ['title' => 'Proyek '.$status->value, 'status' => $status], []);
        }

        $response = $this->halaman($user)->assertOk();
        foreach (ApplicationStatus::cases() as $status) {
            $response->assertSee($status->label());
            $response->assertSee('Proyek '.$status->value);
        }
    }

    public function test_urutan_mengikuti_prioritas_status_lalu_terbaru_dulu(): void
    {
        $user = $this->makeKandidat();
        $this->makeApplication($user, ['title' => 'B-Ditolak', 'status' => ApplicationStatus::Rejected, 'created_at' => '2026-11-02 09:00:00'], []);
        $this->makeApplication($user, ['title' => 'C-Dibatalkan', 'status' => ApplicationStatus::Cancelled, 'created_at' => '2026-11-09 09:00:00'], []);
        $this->makeApplication($user, ['title' => 'D-Menunggu-Lama', 'status' => ApplicationStatus::Pending, 'created_at' => '2026-11-01 09:00:00'], []);
        $this->makeApplication($user, ['title' => 'E-Menunggu-Baru', 'status' => ApplicationStatus::Pending, 'created_at' => '2026-11-06 09:00:00'], []);
        $this->makeApplication($user, ['title' => 'F-Terpilih', 'status' => ApplicationStatus::Shortlisted, 'created_at' => '2026-11-03 09:00:00'], []);
        $this->makeApplication($user, ['title' => 'G-Diterima', 'status' => ApplicationStatus::Accepted, 'created_at' => '2026-11-04 09:00:00'], []);

        $this->halaman($user)->assertSeeInOrder([
            'G-Diterima', 'F-Terpilih', 'E-Menunggu-Baru', 'D-Menunggu-Lama', 'B-Ditolak', 'C-Dibatalkan',
        ]);
    }

    public function test_daftar_dibagi_per_halaman_dan_filter_tetap_terbawa(): void
    {
        $user = $this->makeKandidat();
        $total = CandidateApplicationService::PER_PAGE + 1;
        for ($i = 1; $i <= $total; $i++) {
            $this->makeApplication($user, ['title' => sprintf('Proyek-%02d', $i), 'created_at' => sprintf('2026-11-%02d 09:00:00', $i)], []);
        }

        $this->halaman($user)->assertSee('Halaman 1 dari 2')->assertDontSee('Proyek-01');
        $this->halaman($user, ['page' => 2])->assertOk()->assertSee('Proyek-01')->assertSee('Halaman 2 dari 2');
        $this->halaman($user, ['status' => 'pending'])->assertSee('status=pending&amp;page=2', false);
    }

    // ---------- Catatan seleksi ----------

    public function test_catatan_seleksi_tampil_jika_ada_dan_tidak_dibuat_jika_kosong(): void
    {
        $user = $this->makeKandidat();
        $this->makeApplication($user, ['title' => 'Dengan Catatan', 'status' => ApplicationStatus::Rejected, 'selection_note' => 'Terima kasih atas lamarannya.'], []);
        $this->makeApplication($user, ['title' => 'Tanpa Catatan', 'status' => ApplicationStatus::Pending], []);

        $html = $this->halaman($user)->assertOk()->assertSee('Catatan seleksi:')->assertSee('Terima kasih atas lamarannya.')->getContent();

        $this->assertSame(1, substr_count($html, 'Catatan seleksi:'));
    }

    public function test_catatan_berisi_html_ditampilkan_sebagai_teks(): void
    {
        $user = $this->makeKandidat();
        $this->makeApplication($user, ['status' => ApplicationStatus::Rejected, 'selection_note' => '<script>alert(1)</script>'], []);

        $this->halaman($user)->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false);
    }

    // ---------- Riwayat status ----------

    public function test_riwayat_diurutkan_terbaru_dulu_dan_id_memecah_seri(): void
    {
        $user = $this->makeKandidat();
        $this->makeApplication($user, ['status' => ApplicationStatus::Accepted], [
            [ApplicationStatus::Pending, '2026-11-03 20:14:00', null],
            [ApplicationStatus::Shortlisted, '2026-11-06 11:30:00', 'Masuk daftar pendek'],
            [ApplicationStatus::Accepted, '2026-11-06 11:30:00', 'Diterima setelah wawancara'], // waktu sama, id lebih besar
        ]);

        $this->halaman($user)->assertSeeInOrder([
            'Diterima setelah wawancara', 'Terpilih sementara', 'Lamaran dikirim',
        ])->assertSee('6 Nov 2026, 11.30')->assertSee('3 Nov 2026, 20.14');
    }

    public function test_entri_awal_berlabel_lamaran_dikirim_dan_catatan_umkm_dilabeli(): void
    {
        $user = $this->makeKandidat();
        $this->makeApplication($user, ['status' => ApplicationStatus::Shortlisted, 'selection_note' => 'Anda masuk daftar pendek.'], [
            [ApplicationStatus::Pending, '2026-11-03 20:14:00', null],
            [ApplicationStatus::Shortlisted, '2026-11-06 11:30:00', 'Anda masuk daftar pendek.'],
        ]);

        $this->halaman($user)
            ->assertSee('Riwayat status (2)')
            ->assertSee('Lamaran dikirim')
            ->assertSee('Status awal: Menunggu Keputusan')
            ->assertSee('Catatan UMKM: Anda masuk daftar pendek.');
    }

    public function test_riwayat_terbuka_otomatis_hanya_untuk_lamaran_aktif_dengan_lebih_dari_satu_entri(): void
    {
        $dua = [
            [ApplicationStatus::Pending, '2026-11-03 20:14:00', null],
            [ApplicationStatus::Shortlisted, '2026-11-06 11:30:00', 'x'],
        ];

        // Aktif + 2 entri -> terbuka
        $a = $this->makeKandidat();
        $this->makeApplication($a, ['status' => ApplicationStatus::Shortlisted], $dua);
        $this->assertStringContainsString('<details class="la-hist" open>', $this->halaman($a)->getContent());

        // Aktif + 1 entri -> tertutup
        $b = $this->makeKandidat();
        $this->makeApplication($b, ['status' => ApplicationStatus::Pending]);
        $html = $this->halaman($b)->getContent();
        $this->assertStringContainsString('<details class="la-hist">', $html);
        $this->assertStringNotContainsString('<details class="la-hist" open>', $html);

        // Sudah selesai (Diterima) + 2 entri -> tertutup
        $c = $this->makeKandidat();
        $this->makeApplication($c, ['status' => ApplicationStatus::Accepted], [
            [ApplicationStatus::Pending, '2026-11-03 20:14:00', null],
            [ApplicationStatus::Accepted, '2026-11-06 11:30:00', 'ok'],
        ]);
        $this->assertStringNotContainsString('<details class="la-hist" open>', $this->halaman($c)->getContent());
    }

    public function test_lamaran_dengan_riwayat_awal_saja_atau_tanpa_riwayat_tetap_tampil(): void
    {
        $user = $this->makeKandidat();
        $this->makeApplication($user, ['title' => 'Hanya Riwayat Awal']);
        $this->makeApplication($user, ['title' => 'Tanpa Riwayat', 'status' => ApplicationStatus::Rejected], []);

        $this->halaman($user)
            ->assertOk()
            ->assertSee('Hanya Riwayat Awal')->assertSee('Riwayat status (1)')->assertSee('Lamaran dikirim')
            ->assertSee('Tanpa Riwayat')->assertSee('Ditolak');
    }

    public function test_tanggal_riwayat_kosong_tidak_dikarang(): void
    {
        $user = $this->makeKandidat();
        $this->makeApplication($user, ['status' => ApplicationStatus::Shortlisted], [
            [ApplicationStatus::Pending, null, null],
            [ApplicationStatus::Shortlisted, null, 'Catatan tanpa tanggal'],
        ]);

        $this->halaman($user)->assertOk()->assertSee('Catatan UMKM: Catatan tanpa tanggal');
    }

    public function test_lamaran_dibatalkan_menampilkan_tanggal_dari_riwayat_atau_pesan_netral(): void
    {
        $user = $this->makeKandidat();
        $this->makeApplication($user, ['title' => 'Batal Ada Riwayat', 'status' => ApplicationStatus::Cancelled, 'created_at' => '2026-11-02 09:00:00'], [
            [ApplicationStatus::Pending, '2026-11-02 09:00:00', null],
            [ApplicationStatus::Cancelled, '2026-11-03 10:00:00', null],
        ]);
        $this->makeApplication($user, ['title' => 'Batal Tanpa Riwayat', 'status' => ApplicationStatus::Cancelled], []);

        $this->halaman($user)
            ->assertSee('Dibatalkan oleh Anda pada 3 Nov 2026.')
            ->assertSee('Lamaran ini telah dibatalkan.');
    }

    public function test_keterangan_lamaran_diterima_tidak_dapat_dibatalkan(): void
    {
        $user = $this->makeKandidat();
        $this->makeApplication($user, ['status' => ApplicationStatus::Accepted], []);

        $this->halaman($user)->assertSee('Lamaran yang sudah diterima atau ditolak tidak dapat dibatalkan.');
    }

    // ---------- Filter dan jumlah pada tab ----------

    public function test_jumlah_pada_tab_sesuai_data(): void
    {
        $user = $this->makeKandidat();
        foreach ([ApplicationStatus::Pending, ApplicationStatus::Pending, ApplicationStatus::Accepted, ApplicationStatus::Cancelled] as $status) {
            $this->makeApplication($user, ['status' => $status], []);
        }

        $this->assertSame(
            ['all' => 4, 'pending' => 2, 'shortlisted' => 0, 'accepted' => 1, 'rejected' => 0, 'cancelled' => 1],
            app(CandidateApplicationService::class)->counts($user->candidateProfile),
        );

        $html = $this->halaman($user)->getContent();
        $this->assertMatchesRegularExpression('/Semua<span>4<\/span>/', $html);
        $this->assertMatchesRegularExpression('/Menunggu Keputusan<span>2<\/span>/', $html);
        $this->assertMatchesRegularExpression('/Terpilih Sementara<span>0<\/span>/', $html);
        $this->assertMatchesRegularExpression('/Diterima<span>1<\/span>/', $html);
        $this->assertMatchesRegularExpression('/Dibatalkan<span>1<\/span>/', $html);
    }

    public function test_filter_status_menampilkan_hanya_status_tersebut(): void
    {
        $user = $this->makeKandidat();
        foreach (ApplicationStatus::cases() as $status) {
            $this->makeApplication($user, ['title' => 'Proyek-'.$status->value, 'status' => $status], []);
        }

        foreach (ApplicationStatus::cases() as $status) {
            $response = $this->halaman($user, ['status' => $status->value])->assertOk()->assertSee('Proyek-'.$status->value);
            foreach (ApplicationStatus::cases() as $other) {
                if ($other !== $status) {
                    $response->assertDontSee('Proyek-'.$other->value);
                }
            }
        }
    }

    public function test_status_tidak_dikenal_diperlakukan_sebagai_semua(): void
    {
        $user = $this->makeKandidat();
        $this->makeApplication($user, ['title' => 'Satu', 'status' => ApplicationStatus::Accepted], []);
        $this->makeApplication($user, ['title' => 'Dua', 'status' => ApplicationStatus::Rejected], []);

        $this->halaman($user, ['status' => 'bukan-status'])->assertOk()->assertSee('Satu')->assertSee('Dua');
        $this->halaman($user, ['status' => ['array']])->assertOk()->assertSee('Satu')->assertSee('Dua');
    }

    public function test_filter_tanpa_hasil_menampilkan_pesan(): void
    {
        $user = $this->makeKandidat();
        $this->makeApplication($user, ['status' => ApplicationStatus::Accepted], []);

        $this->halaman($user, ['status' => 'rejected'])->assertOk()->assertSee('Tidak ada lamaran dengan status Ditolak');
    }

    // ---------- Akses dan keadaan kosong ----------

    public function test_pengguna_bukan_kandidat_tidak_dapat_mengakses(): void
    {
        $this->halaman($this->makeUmkm())->assertForbidden();
        $this->halaman(User::factory()->admin()->create())->assertForbidden();
    }

    public function test_kandidat_tanpa_lamaran_atau_tanpa_profil_melihat_keadaan_kosong(): void
    {
        $this->halaman($this->makeKandidat())->assertOk()->assertSee('Belum ada lamaran');
        $this->halaman(User::factory()->kandidat()->create())->assertOk()->assertSee('Belum ada lamaran');
    }

    // ---------- Titik integrasi dengan PBI lain ----------

    public function test_tombol_detail_proyek_mengikuti_ketersediaan_route_pbi14(): void
    {
        $user = $this->makeKandidat();
        $application = $this->makeApplication($user);
        $detailUrl = '/kandidat/proyek/'.$application->project_id;

        if (! Route::has('kandidat.proyek.show')) {
            // PBI-14 belum terpasang: halaman tetap normal dan tidak ada tombol mati.
            $this->halaman($user)->assertOk()->assertDontSee('Detail Proyek');

            Route::get('kandidat/proyek/{project}', fn () => 'ok')->name('kandidat.proyek.show');
            app('router')->getRoutes()->refreshNameLookups();
        }

        $this->halaman($user)->assertOk()->assertSee('Detail Proyek')->assertSee($detailUrl, false);
    }

    public function test_slot_aksi_pbi18_dan_pbi25_hanya_dipanggil_untuk_status_yang_sesuai(): void
    {
        $dir = sys_get_temp_dir().'/pbi17-slot-'.uniqid();
        mkdir($dir.'/kandidat/lamaran', 0777, true);
        file_put_contents($dir.'/kandidat/lamaran/_batalkan.blade.php', 'SLOT-BATALKAN-{{ $application->id }}');
        file_put_contents($dir.'/kandidat/lamaran/_tanggapi-kesepakatan.blade.php', 'SLOT-KESEPAKATAN-{{ $application->id }}');
        View::getFinder()->prependLocation($dir);
        View::getFinder()->flush();

        try {
            $user = $this->makeKandidat();
            $pending = $this->makeApplication($user, ['status' => ApplicationStatus::Pending], []);
            $shortlisted = $this->makeApplication($user, ['status' => ApplicationStatus::Shortlisted], []);
            $accepted = $this->makeApplication($user, ['status' => ApplicationStatus::Accepted], []);
            $rejected = $this->makeApplication($user, ['status' => ApplicationStatus::Rejected], []);
            $cancelled = $this->makeApplication($user, ['status' => ApplicationStatus::Cancelled], []);

            $this->halaman($user)
                ->assertSee('SLOT-BATALKAN-'.$pending->id)
                ->assertSee('SLOT-BATALKAN-'.$shortlisted->id)
                ->assertDontSee('SLOT-BATALKAN-'.$accepted->id)
                ->assertDontSee('SLOT-BATALKAN-'.$rejected->id)
                ->assertDontSee('SLOT-BATALKAN-'.$cancelled->id)
                ->assertSee('SLOT-KESEPAKATAN-'.$accepted->id)
                ->assertDontSee('SLOT-KESEPAKATAN-'.$pending->id)
                ->assertDontSee('SLOT-KESEPAKATAN-'.$rejected->id);
        } finally {
            unlink($dir.'/kandidat/lamaran/_batalkan.blade.php');
            unlink($dir.'/kandidat/lamaran/_tanggapi-kesepakatan.blade.php');
            rmdir($dir.'/kandidat/lamaran');
            rmdir($dir.'/kandidat');
            rmdir($dir);
        }
    }

    public function test_lamaran_yang_dibuat_lewat_service_pbi16_muncul_di_halaman_ini(): void
    {
        $user = $this->makeKandidat();
        $project = $this->makeProject(['title' => 'Proyek Dari PBI-16']);

        app(ApplicationService::class)->submit($user, $project, $this->validMessage());

        $this->halaman($user)
            ->assertOk()
            ->assertSee('Proyek Dari PBI-16')
            ->assertSee('Menunggu Keputusan')
            ->assertSee('Riwayat status (1)')
            ->assertSee('Status awal: Menunggu Keputusan');
        $this->assertSame(1, Application::count());
    }

    // ---------- Kinerja ----------

    public function test_jumlah_query_tidak_bertambah_seiring_jumlah_lamaran(): void
    {
        $user = $this->makeKandidat();
        $this->makeApplication($user, [], [[ApplicationStatus::Pending, '2026-11-03 20:14:00', null], [ApplicationStatus::Shortlisted, '2026-11-04 20:14:00', 'a']]);

        DB::enableQueryLog();
        $this->halaman($user)->assertOk();
        $satu = count(DB::getQueryLog());

        for ($i = 0; $i < 5; $i++) {
            $this->makeApplication($user, [], [[ApplicationStatus::Pending, '2026-11-03 20:14:00', null], [ApplicationStatus::Shortlisted, '2026-11-04 20:14:00', 'a']]);
        }

        DB::flushQueryLog();
        $this->halaman($user)->assertOk();
        $enam = count(DB::getQueryLog());

        $this->assertSame($satu, $enam, 'Jumlah query harus sama (tidak ada N+1).');
    }
}
