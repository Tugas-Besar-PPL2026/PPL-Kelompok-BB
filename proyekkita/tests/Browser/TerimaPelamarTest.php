<?php

namespace Tests\Browser;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\Concerns\BuildsCancellationScenario;
use Tests\DuskTestCase;

/**
 * [PBI-21 / FR-11] Terima pelamar dengan validasi kuota melalui browser.
 *
 * Pelengkap Feature Test (tests/Feature/CandidateAcceptanceTest.php). Data dibuat unik per test.
 * Penerimaan bersamaan (race condition) tidak diuji di sini karena butuh dua proses dan MySQL.
 */
class TerimaPelamarTest extends DuskTestCase
{
    use BuildsCancellationScenario;

    public function test_umkm_menerima_pelamar_dan_kuota_pada_daftar_serta_detail_diperbarui(): void
    {
        $umkm = $this->makeUmkm();
        $project = $this->makeProject(['title' => 'Dusk Terima '.Str::random(6), 'quota' => 2], $umkm->umkmProfile);
        $lamaran = $this->makeApplicationFor($this->makeKandidat(), ApplicationStatus::Pending, ['project' => $project]);
        $this->makeApplicationFor($this->makeKandidat(), ApplicationStatus::Pending, ['project' => $project]);

        $this->browse(function (Browser $browser) use ($umkm, $project, $lamaran) {
            $browser->loginAs($umkm)
                ->visit(route('umkm.seleksi.show', $lamaran))
                ->assertSeeIn('@kuota-panel', '0 dari 2')
                ->click('@opsi-accept')
                ->type('@catatan-seleksi', 'Selamat bergabung.')
                ->press('@simpan-keputusan')
                ->waitForText('Pelamar berhasil diterima.')
                ->assertSeeIn('@kuota-panel', '1 dari 2')
                ->assertAttribute('@progres-kuota-panel', 'aria-valuenow', '50')

                // Daftar pelamar: status, kuota, dan progress bar ikut diperbarui.
                ->visit(route('umkm.seleksi.index', ['proyek' => $project->id]))
                ->assertSeeIn("@status-pelamar-{$lamaran->id}", 'Diterima')
                ->assertSeeIn('@kuota-daftar', 'Kuota kandidat terisi 1 dari 2')
                ->assertAttribute('@progres-kuota-daftar', 'aria-valuenow', '50');
        });

        $this->assertDatabaseHas('applications', ['id' => $lamaran->id, 'status' => 'accepted']);
        $this->assertDatabaseHas('application_status_histories', ['application_id' => $lamaran->id, 'status' => 'accepted']);
    }

    public function test_kuota_penuh_menonaktifkan_pilihan_terima_dan_progress_bar_seratus_persen(): void
    {
        $umkm = $this->makeUmkm();
        $project = $this->makeProject(['title' => 'Dusk Kuota Penuh '.Str::random(6), 'quota' => 1], $umkm->umkmProfile);
        $this->makeApplicationFor($this->makeKandidat(), ApplicationStatus::Accepted, ['project' => $project]);
        $menunggu = $this->makeApplicationFor($this->makeKandidat(), ApplicationStatus::Pending, ['project' => $project]);

        $this->browse(function (Browser $browser) use ($umkm, $project, $menunggu) {
            $browser->loginAs($umkm)
                ->visit(route('umkm.seleksi.index', ['proyek' => $project->id]))
                ->assertSeeIn('@kuota-daftar', 'Kuota kandidat terisi 1 dari 1')
                ->assertAttribute('@progres-kuota-daftar', 'aria-valuenow', '100')

                ->visit(route('umkm.seleksi.show', $menunggu))
                ->assertSeeIn('@kuota-panel', '1 dari 1')
                ->assertSeeIn('@opsi-accept', 'Kuota sudah terpenuhi')
                ->assertPresent('@opsi-accept input[disabled]');
        });

        $this->assertSame(ApplicationStatus::Pending, Application::findOrFail($menunggu->id)->status);
    }

    public function test_penerimaan_terakhir_mengisi_kuota_lalu_pelamar_berikutnya_tidak_bisa_diterima(): void
    {
        $umkm = $this->makeUmkm();
        $project = $this->makeProject(['title' => 'Dusk Slot Terakhir '.Str::random(6), 'quota' => 1], $umkm->umkmProfile);
        $pertama = $this->makeApplicationFor($this->makeKandidat(), ApplicationStatus::Pending, ['project' => $project]);
        $kedua = $this->makeApplicationFor($this->makeKandidat(), ApplicationStatus::Pending, ['project' => $project]);

        $this->browse(function (Browser $browser) use ($umkm, $pertama, $kedua) {
            $browser->loginAs($umkm)
                ->visit(route('umkm.seleksi.show', $pertama))
                ->click('@opsi-accept')
                ->press('@simpan-keputusan')
                ->waitForText('Pelamar berhasil diterima.')
                ->assertAttribute('@progres-kuota-panel', 'aria-valuenow', '100')

                ->visit(route('umkm.seleksi.show', $kedua))
                ->assertSeeIn('@opsi-accept', 'Kuota sudah terpenuhi')
                ->assertPresent('@opsi-accept input[disabled]');
        });

        $this->assertSame(ApplicationStatus::Accepted, Application::findOrFail($pertama->id)->status);
        $this->assertSame(ApplicationStatus::Pending, Application::findOrFail($kedua->id)->status);
    }
}
