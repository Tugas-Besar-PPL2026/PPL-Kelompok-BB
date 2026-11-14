<?php

namespace Tests\Feature\Kandidat;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\User;
use App\Services\ApplicationCancellationService;
use App\Services\ApplicationService;
use App\Services\CandidateSelectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\Concerns\BuildsCancellationScenario;
use Tests\Concerns\UsesInMemoryDatabase;
use Tests\TestCase;

/**
 * [PBI-18 / FR-10] Kandidat membatalkan lamaran.
 *
 * Catatan: test "lamaran ulang ditolak dengan pesan jelas" dan "halaman Detail Proyek untuk lamaran dibatalkan"
 * mensyaratkan dua perubahan teks kecil pada file PBI-16 (lihat panduan pemasangan PBI-18).
 */
class BatalkanLamaranTest extends TestCase
{
    use BuildsCancellationScenario;
    use RefreshDatabase, UsesInMemoryDatabase;

    private function batalkan(User $user, Application|int $application, array $data = [])
    {
        $id = $application instanceof Application ? $application->id : $application;

        return $this->actingAs($user)->patch(route('kandidat.lamaran.cancel', $id), $data);
    }

    // =====================================================================
    // Skenario positif
    // =====================================================================

    public static function statusYangDapatDibatalkan(): array
    {
        return [
            'Menunggu Keputusan' => [ApplicationStatus::Pending],
            'Terpilih Sementara' => [ApplicationStatus::Shortlisted],
        ];
    }

    #[DataProvider('statusYangDapatDibatalkan')]
    public function test_kandidat_berhasil_membatalkan_lamaran_yang_masih_dapat_dibatalkan(ApplicationStatus $awal): void
    {
        $kandidat = $this->makeKandidat();
        $application = $this->makeApplicationFor($kandidat, $awal);

        $this->batalkan($kandidat, $application)
            ->assertRedirect(route('kandidat.lamaran.index'))
            ->assertSessionHas('success', 'Lamaran berhasil dibatalkan dan berstatus Dibatalkan.')
            ->assertSessionMissing('error');
    }

    #[DataProvider('statusYangDapatDibatalkan')]
    public function test_status_lamaran_berubah_menjadi_dibatalkan(ApplicationStatus $awal): void
    {
        $kandidat = $this->makeKandidat();
        $application = $this->makeApplicationFor($kandidat, $awal);

        $this->batalkan($kandidat, $application);

        $this->assertSame(ApplicationStatus::Cancelled, $application->fresh()->status);
        $this->assertDatabaseHas('applications', ['id' => $application->id, 'status' => 'cancelled']);
    }

    public function test_riwayat_pembatalan_tersimpan_dan_riwayat_lama_tidak_berubah(): void
    {
        $kandidat = $this->makeKandidat();
        $application = $this->makeApplicationFor($kandidat, ApplicationStatus::Shortlisted, [
            'histories' => [[ApplicationStatus::Shortlisted, '2026-11-06 11:30:00', 'Masuk daftar pendek']],
        ]);
        $this->assertSame(2, $this->historyCountOf($application));

        $this->batalkan($kandidat, $application);

        $this->assertSame(3, $this->historyCountOf($application));

        $terbaru = ApplicationStatusHistory::where('application_id', $application->id)->orderByDesc('id')->first();
        $this->assertSame(ApplicationStatus::Cancelled, $terbaru->status);
        $this->assertSame('Dibatalkan oleh kandidat.', $terbaru->note);
        $this->assertNotNull($terbaru->changed_at);

        // Entri lama tetap utuh.
        $this->assertDatabaseHas('application_status_histories', [
            'application_id' => $application->id, 'status' => 'shortlisted', 'note' => 'Masuk daftar pendek',
        ]);
        $this->assertDatabaseHas('application_status_histories', ['application_id' => $application->id, 'status' => 'pending']);
    }

    public function test_data_lamaran_tetap_tersimpan_dan_relasinya_utuh(): void
    {
        $kandidat = $this->makeKandidat();
        $application = $this->makeApplicationFor($kandidat, ApplicationStatus::Shortlisted, [
            'selection_note' => 'Anda masuk daftar pendek.',
            'created_at' => '2026-11-03 20:14:00',
        ]);
        $sebelum = $application->only(['project_id', 'candidate_profile_id', 'message', 'selection_note', 'created_at']);

        $this->batalkan($kandidat, $application);

        $sesudah = Application::findOrFail($application->id);
        $this->assertSame(1, Application::count(), 'Lamaran tidak boleh dihapus.');
        $this->assertEquals($sebelum, $sesudah->only(['project_id', 'candidate_profile_id', 'message', 'selection_note', 'created_at']));
        $this->assertSame('Anda masuk daftar pendek.', $sesudah->selection_note, 'Catatan UMKM tidak boleh diubah.');

        $this->assertNotNull($sesudah->project);
        $this->assertSame($kandidat->candidateProfile->id, $sesudah->candidateProfile->id);
        $this->assertNotNull($kandidat->fresh()->candidateProfile);
        $this->assertSame(2, $sesudah->statusHistories()->count());
    }

    public function test_pembatalan_diulang_hanya_tercatat_sekali(): void
    {
        $kandidat = $this->makeKandidat();
        $application = $this->makeApplicationFor($kandidat);

        $this->batalkan($kandidat, $application)->assertSessionHas('success');
        $this->batalkan($kandidat, $application)->assertSessionHas('error', 'Lamaran ini sudah dibatalkan sebelumnya.');

        $this->assertSame(2, $this->historyCountOf($application), 'Entri awal + satu entri pembatalan.');
    }

    // =====================================================================
    // Skenario negatif
    // =====================================================================

    public static function statusYangTidakDapatDibatalkan(): array
    {
        return [
            'Diterima' => [ApplicationStatus::Accepted, 'Lamaran yang sudah diterima tidak dapat dibatalkan.'],
            'Ditolak' => [ApplicationStatus::Rejected, 'Lamaran yang sudah ditolak tidak dapat dibatalkan.'],
            'Dibatalkan' => [ApplicationStatus::Cancelled, 'Lamaran ini sudah dibatalkan sebelumnya.'],
        ];
    }

    #[DataProvider('statusYangTidakDapatDibatalkan')]
    public function test_lamaran_dengan_status_final_tidak_dapat_dibatalkan_dan_tidak_berubah(ApplicationStatus $status, string $pesan): void
    {
        $kandidat = $this->makeKandidat();
        $application = $this->makeApplicationFor($kandidat, $status);
        $riwayatSebelum = $this->historyCountOf($application);

        $this->batalkan($kandidat, $application)
            ->assertRedirect(route('kandidat.lamaran.index'))
            ->assertSessionHas('error', $pesan)
            ->assertSessionMissing('success');

        $this->assertSame($status, $application->fresh()->status);
        $this->assertSame($riwayatSebelum, $this->historyCountOf($application), 'Riwayat tidak boleh bertambah.');
    }

    public function test_kandidat_tidak_dapat_membatalkan_lamaran_milik_kandidat_lain(): void
    {
        $pemilik = $this->makeKandidat();
        $penyerang = $this->makeKandidat();
        $milikPemilik = $this->makeApplicationFor($pemilik, ApplicationStatus::Pending);
        $milikPenyerang = $this->makeApplicationFor($penyerang, ApplicationStatus::Pending);

        $this->batalkan($penyerang, $milikPemilik)->assertNotFound();

        $this->assertSame(ApplicationStatus::Pending, $milikPemilik->fresh()->status);
        $this->assertSame(1, $this->historyCountOf($milikPemilik));
        $this->assertSame(ApplicationStatus::Pending, $milikPenyerang->fresh()->status, 'Lamaran penyerang sendiri juga tidak tersentuh.');
    }

    public function test_lamaran_yang_tidak_ditemukan_menghasilkan_404(): void
    {
        $kandidat = $this->makeKandidat();
        $this->makeApplicationFor($kandidat);

        $this->batalkan($kandidat, 999999)->assertNotFound();
        $this->actingAs($kandidat)->patch('/kandidat/lamaran/abc/batal')->assertNotFound();
        $this->assertSame(ApplicationStatus::Pending, Application::first()->status);
    }

    public function test_pengguna_bukan_kandidat_tidak_dapat_membatalkan(): void
    {
        $kandidat = $this->makeKandidat();
        $application = $this->makeApplicationFor($kandidat);
        $umkmPemilikProyek = $application->project->umkmProfile->user;

        $this->batalkan($umkmPemilikProyek, $application)->assertForbidden();
        $this->batalkan(User::factory()->admin()->create(), $application)->assertForbidden();

        $this->assertSame(ApplicationStatus::Pending, $application->fresh()->status);
        $this->assertSame(1, $this->historyCountOf($application));
    }

    public function test_kandidat_tanpa_profil_tidak_dapat_membatalkan(): void
    {
        $application = $this->makeApplicationFor($this->makeKandidat());
        $tanpaProfil = User::factory()->kandidat()->create();

        $this->batalkan($tanpaProfil, $application)->assertNotFound();
        $this->assertSame(ApplicationStatus::Pending, $application->fresh()->status);
    }

    public function test_status_dan_riwayat_kembali_semula_bila_penulisan_riwayat_gagal(): void
    {
        $kandidat = $this->makeKandidat();
        $application = $this->makeApplicationFor($kandidat, ApplicationStatus::Shortlisted);

        ApplicationStatusHistory::creating(function () {
            throw new RuntimeException('Simulasi kegagalan menulis riwayat.');
        });

        try {
            app(ApplicationCancellationService::class)->cancel($kandidat, $application->id);
            $this->fail('Pembatalan seharusnya gagal.');
        } catch (RuntimeException $e) {
            $this->assertSame('Simulasi kegagalan menulis riwayat.', $e->getMessage());
        } finally {
            ApplicationStatusHistory::flushEventListeners();
        }

        $this->assertSame(ApplicationStatus::Shortlisted, $application->fresh()->status, 'Perubahan status harus ikut dibatalkan (transaksi).');
        $this->assertSame(1, $this->historyCountOf($application));
    }

    public function test_status_yang_diubah_umkm_sesaat_sebelum_pembatalan_membuat_pembatalan_ditolak(): void
    {
        $kandidat = $this->makeKandidat();
        $application = $this->makeApplicationFor($kandidat, ApplicationStatus::Shortlisted);

        // Kandidat membuka halaman: tombol masih tampil.
        $this->actingAs($kandidat)->get(route('kandidat.lamaran.index'))->assertSee('Batalkan Lamaran');

        // Sebelum kandidat menekan konfirmasi, UMKM menerima lamaran itu.
        Application::whereKey($application->id)->update(['status' => ApplicationStatus::Accepted->value]);

        $this->batalkan($kandidat, $application)
            ->assertSessionHas('error', 'Lamaran yang sudah diterima tidak dapat dibatalkan.');

        $this->assertSame(ApplicationStatus::Accepted, $application->fresh()->status);
        $this->assertSame(1, $this->historyCountOf($application));
    }

    // =====================================================================
    // Pengalihan dan tab filter
    // =====================================================================

    public function test_tab_filter_aktif_dipertahankan_dan_halaman_kembali_ke_awal(): void
    {
        $kandidat = $this->makeKandidat();
        $application = $this->makeApplicationFor($kandidat, ApplicationStatus::Shortlisted);

        $this->batalkan($kandidat, $application, ['status' => 'shortlisted', 'page' => 3])
            ->assertRedirect(route('kandidat.lamaran.index', ['status' => 'shortlisted']));
    }

    public function test_nilai_filter_yang_tidak_dikenal_diabaikan(): void
    {
        $kandidat = $this->makeKandidat();

        $a = $this->makeApplicationFor($kandidat);
        $this->batalkan($kandidat, $a, ['status' => '<script>'])->assertRedirect(route('kandidat.lamaran.index'));

        $b = $this->makeApplicationFor($kandidat);
        $this->batalkan($kandidat, $b, ['status' => ['x']])->assertRedirect(route('kandidat.lamaran.index'));
    }

    // =====================================================================
    // Tampilan pada halaman Lamaran Saya (PBI-17)
    // =====================================================================

    public function test_tombol_batalkan_hanya_muncul_pada_lamaran_yang_dapat_dibatalkan(): void
    {
        $kandidat = $this->makeKandidat();
        $apps = [];
        foreach (ApplicationStatus::cases() as $status) {
            $apps[$status->value] = $this->makeApplicationFor($kandidat, $status);
        }

        $html = $this->actingAs($kandidat)->get(route('kandidat.lamaran.index'))->assertOk()->getContent();

        $this->assertSame(2, substr_count($html, 'data-batal-open='), 'Tepat dua tombol: Menunggu Keputusan dan Terpilih Sementara.');

        foreach ([ApplicationStatus::Pending, ApplicationStatus::Shortlisted] as $status) {
            $id = $apps[$status->value]->id;
            $this->assertStringContainsString('dusk="batalkan-lamaran-'.$id.'"', $html);
            $this->assertStringContainsString('/kandidat/lamaran/'.$id.'/batal', $html);
        }
        foreach ([ApplicationStatus::Accepted, ApplicationStatus::Rejected, ApplicationStatus::Cancelled] as $status) {
            $id = $apps[$status->value]->id;
            $this->assertStringNotContainsString('batalkan-lamaran-'.$id.'"', $html);
            $this->assertStringNotContainsString('/kandidat/lamaran/'.$id.'/batal', $html);
        }
    }

    public function test_dialog_konfirmasi_menjelaskan_perubahan_status(): void
    {
        $kandidat = $this->makeKandidat();
        $application = $this->makeApplicationFor($kandidat, ApplicationStatus::Pending, ['title' => 'Logo Kedai Kopi']);

        $this->actingAs($kandidat)->get(route('kandidat.lamaran.index'))
            ->assertOk()
            ->assertSee('Batalkan lamaran?')
            ->assertSee('Logo Kedai Kopi')
            ->assertSee('akan diubah statusnya menjadi')
            ->assertSee('Dibatalkan')
            ->assertSee('tidak dapat mengajukan lamaran kembali pada proyek ini')
            ->assertSee('Data lamaran dan riwayat statusnya tetap tersimpan')
            ->assertSee('Tidak, Kembali')
            ->assertSee('Ya, Batalkan Lamaran')
            ->assertSee('dusk="dialog-batal-'.$application->id.'"', false)
            ->assertSee('dusk="konfirmasi-batal-'.$application->id.'"', false)
            ->assertSee('name="_method" value="PATCH"', false)
            ->assertSee('name="_token"', false);
    }

    public function test_tab_filter_aktif_ikut_terkirim_dari_formulir_dialog(): void
    {
        $kandidat = $this->makeKandidat();
        $this->makeApplicationFor($kandidat, ApplicationStatus::Pending);

        $this->actingAs($kandidat)->get(route('kandidat.lamaran.index', ['status' => 'pending']))
            ->assertSee('<input type="hidden" name="status" value="pending">', false);

        $this->get(route('kandidat.lamaran.index'))
            ->assertDontSee('<input type="hidden" name="status"', false);
    }

    public function test_hasil_pembatalan_tampil_pada_halaman_lamaran_saya(): void
    {
        $kandidat = $this->makeKandidat();
        $application = $this->makeApplicationFor($kandidat, ApplicationStatus::Shortlisted, ['title' => 'Proyek Yang Dibatalkan']);
        $lain = $this->makeApplicationFor($kandidat, ApplicationStatus::Pending, ['title' => 'Proyek Yang Tetap Aktif']);

        $html = $this->actingAs($kandidat)->followingRedirects()
            ->patch(route('kandidat.lamaran.cancel', $application->id))
            ->assertOk()
            ->assertSee('Lamaran berhasil dibatalkan dan berstatus Dibatalkan.')
            ->getContent();

        // Tombol hilang pada lamaran yang dibatalkan, tetap ada pada lamaran lain.
        $this->assertStringNotContainsString('batalkan-lamaran-'.$application->id.'"', $html);
        $this->assertStringContainsString('batalkan-lamaran-'.$lain->id.'"', $html);

        // Tab menghitung ulang, timeline mencatat peristiwa pembatalan.
        $this->assertMatchesRegularExpression('/Terpilih Sementara<span>0<\/span>/', $html);
        $this->assertMatchesRegularExpression('/Dibatalkan<span>1<\/span>/', $html);
        $this->assertStringContainsString('Riwayat status (2)', $html);
    }

    public function test_pelaku_pembatalan_tidak_ditampilkan_berulang_pada_kartu(): void
    {
        $kandidat = $this->makeKandidat();
        $application = $this->makeApplicationFor($kandidat, ApplicationStatus::Pending);
        $this->batalkan($kandidat, $application);

        $html = $this->actingAs($kandidat)->get(route('kandidat.lamaran.index'))->getContent();

        // Keterangan kartu menyebut pelaku sekali, catatan riwayat menyebutnya sekali (satu kali per komponen).
        $this->assertSame(1, substr_count($html, 'Dibatalkan oleh Anda pada'));
        $this->assertSame(1, substr_count($html, 'Dibatalkan oleh kandidat.'));
    }

    // =====================================================================
    // Integrasi dengan PBI lain (hanya membaca / tidak berubah)
    // =====================================================================

    /**
     * Sisi UMKM (PBI-19) menampilkan pelamar menurut status lamaran. Test ini memeriksa dampak pembatalan pada data
     * (lamaran Dibatalkan tidak lagi menjadi pelamar aktif dan jumlah Diterima/kuota tidak berubah) tanpa bergantung
     * pada nama method internal PBI-19, karena kode itu dikerjakan anggota lain dan bisa berubah.
     * Bila CandidateSelectionService menyediakan counts(), hasilnya ikut diperiksa.
     */
    public function test_lamaran_dibatalkan_tidak_lagi_menjadi_pelamar_aktif_dan_kuota_tidak_berubah_pbi19(): void
    {
        $kandidat = $this->makeKandidat();
        $application = $this->makeApplicationFor($kandidat, ApplicationStatus::Shortlisted);
        $project = $application->project;

        $statusAktif = array_map(fn (ApplicationStatus $s) => $s->value, [
            ApplicationStatus::Pending, ApplicationStatus::Shortlisted, ApplicationStatus::Accepted, ApplicationStatus::Rejected,
        ]);
        $pelamarAktif = fn () => $project->applications()->whereIn('status', $statusAktif)->count();
        $diterima = fn () => $project->applications()->where('status', ApplicationStatus::Accepted->value)->count();

        $this->assertSame(1, $pelamarAktif());
        $this->assertSame(0, $diterima());

        $this->batalkan($kandidat, $application);

        $this->assertSame(0, $pelamarAktif(), 'Lamaran Dibatalkan tidak boleh lagi dihitung sebagai pelamar aktif.');
        $this->assertSame(0, $diterima(), 'Jumlah Diterima (kuota) tidak boleh berubah.');
        $this->assertSame(1, $project->applications()->count(), 'Lamaran tetap ada di database.');

        $selection = app(CandidateSelectionService::class);
        if (method_exists($selection, 'counts')) {
            $this->assertSame(0, $selection->counts($project)['shortlisted']);
        }
    }

    public function test_lamaran_ulang_pada_proyek_yang_dibatalkan_ditolak_dengan_pesan_jelas_pbi16(): void
    {
        $kandidat = $this->makeKandidat();
        $application = $this->makeApplicationFor($kandidat);
        $this->batalkan($kandidat, $application);

        $pesan = 'Anda sudah pernah mengajukan lamaran pada proyek ini. Lamaran yang sudah dibatalkan tidak dapat diajukan kembali.';

        // Lewat HTTP (jalur pengguna).
        $this->actingAs($kandidat)
            ->post(route('kandidat.proyek.lamaran.store', $application->project_id), ['message' => $this->validMessage()])
            ->assertSessionHas('error', $pesan);

        // Lewat service PBI-16.
        try {
            app(ApplicationService::class)->submit($kandidat, $application->project, $this->validMessage());
            $this->fail('Pengajuan ulang seharusnya ditolak.');
        } catch (\App\Exceptions\ApplicationNotAllowedException $e) {
            $this->assertSame($pesan, $e->getMessage());
        }

        $this->assertSame(1, Application::where('project_id', $application->project_id)->count());
    }

    public function test_pesan_lamaran_ganda_untuk_lamaran_aktif_tidak_berubah_pbi16(): void
    {
        $kandidat = $this->makeKandidat();
        $application = $this->makeApplicationFor($kandidat, ApplicationStatus::Pending);

        $this->actingAs($kandidat)
            ->post(route('kandidat.proyek.lamaran.store', $application->project_id), ['message' => $this->validMessage()])
            ->assertSessionHas('error', 'Anda sudah pernah melamar proyek ini. Satu kandidat hanya dapat melamar sekali untuk setiap proyek.');
    }

    public function test_halaman_detail_proyek_menjelaskan_lamaran_yang_sudah_dibatalkan_pbi14_pbi16(): void
    {
        $kandidat = $this->makeKandidat();
        $application = $this->makeApplicationFor($kandidat);
        $this->batalkan($kandidat, $application);

        $html = $this->actingAs($kandidat)
            ->get(route('kandidat.proyek.show', $application->project_id))
            ->assertOk()
            ->assertSee('Lamaran Anda sudah dibatalkan')
            ->assertSee('tidak dapat diajukan kembali pada proyek ini')
            ->assertSee('Lamaran Anda pada proyek ini sudah dibatalkan')
            ->assertDontSee('Lamaran Anda sudah terkirim')
            ->getContent();

        preg_match('/<button[^>]*dusk="kirim-lamaran"[^>]*>/', $html, $tombol);
        $this->assertStringContainsString('disabled', $tombol[0] ?? '', 'Tombol Kirim Lamaran tetap nonaktif.');
    }
}
