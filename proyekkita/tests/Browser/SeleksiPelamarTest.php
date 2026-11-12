<?php

namespace Tests\Browser;

use App\Enums\ApplicationStatus;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\Concerns\BuildsCancellationScenario;
use Tests\DuskTestCase;

/**
 * [PBI-19 / FR-11] UMKM melihat daftar pelamar dan membuka detail pelamar melalui browser.
 *
 * Pelengkap Feature Test (tests/Feature/CandidateSelectionTest.php). Data dibuat unik per test.
 * Selector memakai atribut dusk pada umkm/seleksi/index.blade.php dan _keputusan.blade.php.
 */
class SeleksiPelamarTest extends DuskTestCase
{
    use BuildsCancellationScenario;

    public function test_umkm_melihat_daftar_pelamar_dan_membuka_detail_yang_sesuai(): void
    {
        $umkm = $this->makeUmkm();
        $project = $this->makeProject(['title' => 'Dusk Seleksi '.Str::random(6), 'quota' => 2], $umkm->umkmProfile);

        $pertama = $this->makeKandidat();
        $kedua = $this->makeKandidat();
        $lamaranPertama = $this->makeApplicationFor($pertama, ApplicationStatus::Pending, ['project' => $project]);
        $lamaranKedua = $this->makeApplicationFor($kedua, ApplicationStatus::Shortlisted, ['project' => $project]);

        $namaKedua = $kedua->candidateProfile->full_name;

        $this->browse(function (Browser $browser) use ($umkm, $project, $lamaranPertama, $lamaranKedua, $pertama, $namaKedua) {
            $browser->loginAs($umkm)
                ->visit(route('umkm.seleksi.index', ['proyek' => $project->id]))
                ->assertSee('Seleksi Kandidat')
                ->assertSee($project->title)
                ->assertSee($pertama->candidateProfile->full_name)
                ->assertSee($namaKedua)
                ->assertSeeIn('@kuota-daftar', 'Kuota kandidat terisi 0 dari 2')
                ->assertSeeIn("@status-pelamar-{$lamaranPertama->id}", 'Menunggu Keputusan')
                ->assertSeeIn("@status-pelamar-{$lamaranKedua->id}", 'Terpilih Sementara')

                // Tombol Lihat pada pelamar kedua harus membuka detail pelamar kedua.
                ->click("@lihat-pelamar-{$lamaranKedua->id}")
                ->assertPathIs("/umkm/seleksi/pelamar/{$lamaranKedua->id}")
                ->assertSee('Detail Pelamar')
                ->assertSee($namaKedua)
                ->assertSee('Pesan Lamaran')
                ->assertSee($this->validMessage())
                ->assertPresent('@panel-keputusan');
        });
    }

    public function test_filter_tab_status_hanya_menampilkan_pelamar_dengan_status_tersebut(): void
    {
        $umkm = $this->makeUmkm();
        $project = $this->makeProject(['title' => 'Dusk Tab '.Str::random(6)], $umkm->umkmProfile);

        $menunggu = $this->makeKandidat();
        $ditolak = $this->makeKandidat();
        $this->makeApplicationFor($menunggu, ApplicationStatus::Pending, ['project' => $project]);
        $this->makeApplicationFor($ditolak, ApplicationStatus::Rejected, ['project' => $project]);

        $this->browse(function (Browser $browser) use ($umkm, $project, $menunggu, $ditolak) {
            $browser->loginAs($umkm)
                ->visit(route('umkm.seleksi.index', ['proyek' => $project->id]))
                ->clickLink('Ditolak')
                ->assertQueryStringHas('status', 'rejected')
                ->assertSee($ditolak->candidateProfile->full_name)
                ->assertDontSee($menunggu->candidateProfile->full_name);
        });
    }
}
