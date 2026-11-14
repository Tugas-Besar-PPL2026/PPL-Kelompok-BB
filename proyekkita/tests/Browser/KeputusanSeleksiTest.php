<?php

namespace Tests\Browser;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\Concerns\BuildsCancellationScenario;
use Tests\DuskTestCase;

/**
 * [PBI-20 / FR-11] Terpilih Sementara dan Tolak dengan Catatan melalui browser.
 *
 * Pelengkap Feature Test (tests/Feature/CandidateDecisionTest.php). Data dibuat unik per test.
 * Pilihan keputusan diklik lewat label (@opsi-...) karena radio aslinya disembunyikan oleh desain.
 */
class KeputusanSeleksiTest extends DuskTestCase
{
    use BuildsCancellationScenario;

    /** @return array{0: \App\Models\User, 1: \App\Models\Project, 2: Application} */
    private function siapkan(ApplicationStatus $status = ApplicationStatus::Pending): array
    {
        $umkm = $this->makeUmkm();
        $project = $this->makeProject(['title' => 'Dusk Keputusan '.Str::random(6), 'quota' => 2], $umkm->umkmProfile);
        $lamaran = $this->makeApplicationFor($this->makeKandidat(), $status, ['project' => $project]);

        return [$umkm, $project, $lamaran];
    }

    public function test_umkm_memasukkan_pelamar_ke_terpilih_sementara_tanpa_catatan(): void
    {
        [$umkm, , $lamaran] = $this->siapkan();
        $riwayatAwal = $this->historyCountOf($lamaran);

        $this->browse(function (Browser $browser) use ($umkm, $lamaran) {
            $browser->loginAs($umkm)
                ->visit(route('umkm.seleksi.show', $lamaran))
                ->click('@opsi-shortlist')
                ->press('@simpan-keputusan')
                ->waitForText('Pelamar dimasukkan ke daftar terpilih sementara.')
                ->assertSeeIn('@panel-keputusan', 'Kuota kandidat');
        });

        $this->assertSame(ApplicationStatus::Shortlisted, Application::findOrFail($lamaran->id)->status);
        $this->assertSame($riwayatAwal + 1, $this->historyCountOf($lamaran));
    }

    public function test_penolakan_tanpa_catatan_ditolak_dengan_pesan_validasi(): void
    {
        [$umkm, , $lamaran] = $this->siapkan();

        $this->browse(function (Browser $browser) use ($umkm, $lamaran) {
            $browser->loginAs($umkm)
                ->visit(route('umkm.seleksi.show', $lamaran))
                ->click('@opsi-reject')
                ->press('@simpan-keputusan')
                ->waitFor('@galat-keputusan')
                ->assertSeeIn('@galat-keputusan', 'Catatan penolakan wajib diisi');
        });

        $this->assertSame(ApplicationStatus::Pending, Application::findOrFail($lamaran->id)->status);
    }

    public function test_umkm_menolak_pelamar_dengan_catatan_dan_panel_terkunci(): void
    {
        [$umkm, $project, $lamaran] = $this->siapkan();
        $catatan = 'Belum sesuai kebutuhan proyek ini.';

        $this->browse(function (Browser $browser) use ($umkm, $project, $lamaran, $catatan) {
            $browser->loginAs($umkm)
                ->visit(route('umkm.seleksi.show', $lamaran))
                ->click('@opsi-reject')
                ->type('@catatan-seleksi', $catatan)
                ->press('@simpan-keputusan')
                ->waitForText('Pelamar ditolak dan catatan penolakan tersimpan.')
                ->assertPresent('@status-final')
                ->assertMissing('@simpan-keputusan')
                ->assertSee($catatan)

                // Status baru tampil pada daftar pelamar.
                ->visit(route('umkm.seleksi.index', ['proyek' => $project->id]))
                ->assertSeeIn("@status-pelamar-{$lamaran->id}", 'Ditolak');
        });

        $this->assertDatabaseHas('applications', [
            'id' => $lamaran->id, 'status' => 'rejected', 'selection_note' => $catatan,
        ]);
        $this->assertDatabaseHas('application_status_histories', [
            'application_id' => $lamaran->id, 'status' => 'rejected', 'note' => $catatan,
        ]);
    }

    public function test_pelamar_yang_sudah_diterima_tidak_dapat_diubah_lagi(): void
    {
        [$umkm, , $lamaran] = $this->siapkan(ApplicationStatus::Accepted);

        $this->browse(function (Browser $browser) use ($umkm, $lamaran) {
            $browser->loginAs($umkm)
                ->visit(route('umkm.seleksi.show', $lamaran))
                ->assertPresent('@status-final')
                ->assertSeeIn('@status-final', 'tidak dapat diubah lagi')
                ->assertMissing('@simpan-keputusan');
        });

        $this->assertSame(ApplicationStatus::Accepted, Application::findOrFail($lamaran->id)->status);
    }
}
