<?php

namespace Tests\Browser;

use App\Enums\ApplicationStatus;
use Laravel\Dusk\Browser;
use Tests\Concerns\BuildsCancellationScenario;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

/**
 * PBI-17 (FR-10, bagian memantau status dan riwayat) - interaksi halaman "Lamaran Saya" di browser:
 * tab filter, buka/tutup riwayat, pagination, keadaan kosong, dan navigasi ke detail proyek.
 *
 * Database: lihat Tests\Concerns\UsesDuskTestDatabase (wajib memakai database/dusk.sqlite).
 */
class LamaranSayaTest extends DuskTestCase
{
    use BuildsCancellationScenario;
    use UsesDuskTestDatabase;

    public function test_daftar_menampilkan_proyek_status_dan_catatan_seleksi(): void
    {
        $kandidat = $this->makeKandidat();
        $application = $this->makeApplicationFor($kandidat, ApplicationStatus::Rejected, [
            'title' => 'Dusk Daftar Lamaran',
            'selection_note' => 'Terima kasih atas lamarannya.',
            'histories' => [[ApplicationStatus::Rejected, '2026-11-06 11:30:00', 'Terima kasih atas lamarannya.']],
        ]);
        $kartu = "#lamaran-{$application->id}";

        $this->browse(function (Browser $browser) use ($kandidat, $kartu) {
            $browser->loginAs($kandidat)
                ->visit(route('kandidat.lamaran.index'))
                ->assertSeeIn($kartu, 'Dusk Daftar Lamaran')
                ->assertSeeIn($kartu, 'Ditolak')
                ->assertSeeIn($kartu, 'Dilamar 4 Nov 2026')
                ->assertSeeIn($kartu, 'Catatan seleksi:')
                ->assertSeeIn($kartu, 'Terima kasih atas lamarannya.');
        });
    }

    public function test_tab_status_memfilter_daftar(): void
    {
        $kandidat = $this->makeKandidat();
        $this->makeApplicationFor($kandidat, ApplicationStatus::Pending, ['title' => 'Dusk Tab Menunggu']);
        $this->makeApplicationFor($kandidat, ApplicationStatus::Shortlisted, ['title' => 'Dusk Tab Terpilih']);
        $this->makeApplicationFor($kandidat, ApplicationStatus::Accepted, ['title' => 'Dusk Tab Diterima']);

        $this->browse(function (Browser $browser) use ($kandidat) {
            $browser->loginAs($kandidat)
                ->visit(route('kandidat.lamaran.index'))
                ->assertSee('Dusk Tab Menunggu')
                ->assertSee('Dusk Tab Terpilih')
                ->assertSee('Dusk Tab Diterima')
                ->click('.la-tabs a[href*="status=shortlisted"]')
                ->assertQueryStringHas('status', 'shortlisted')
                ->assertSee('Dusk Tab Terpilih')
                ->assertDontSee('Dusk Tab Menunggu')
                ->assertDontSee('Dusk Tab Diterima')
                ->assertSeeIn('.la-tabs a.on', 'Terpilih Sementara')
                ->click('.la-tabs a:first-child')
                ->assertQueryStringMissing('status')
                ->assertSee('Dusk Tab Menunggu')
                ->assertSee('Dusk Tab Terpilih')
                ->assertSee('Dusk Tab Diterima');
        });
    }

    public function test_riwayat_dapat_dibuka_dan_ditutup(): void
    {
        $kandidat = $this->makeKandidat();
        $application = $this->makeApplicationFor($kandidat, ApplicationStatus::Pending);
        $riwayat = "#lamaran-{$application->id} .la-hist";

        $this->browse(function (Browser $browser) use ($kandidat, $riwayat) {
            $browser->loginAs($kandidat)
                ->visit(route('kandidat.lamaran.index'))
                ->assertMissing("{$riwayat} .tl")
                ->click("{$riwayat} summary")
                ->waitFor("{$riwayat} .tl")
                ->assertSeeIn("{$riwayat} .tl", 'Lamaran dikirim')
                ->click("{$riwayat} summary")
                ->waitUntilMissing("{$riwayat} .tl");
        });
    }

    public function test_riwayat_lamaran_aktif_dengan_dua_entri_terbuka_otomatis(): void
    {
        $kandidat = $this->makeKandidat();
        $application = $this->makeApplicationFor($kandidat, ApplicationStatus::Shortlisted, [
            'histories' => [[ApplicationStatus::Shortlisted, '2026-11-05 09:00:00', 'Masuk daftar pendek']],
        ]);
        $riwayat = "#lamaran-{$application->id} .la-hist";

        $this->browse(function (Browser $browser) use ($kandidat, $riwayat) {
            $browser->loginAs($kandidat)
                ->visit(route('kandidat.lamaran.index'))
                ->assertVisible("{$riwayat} .tl")
                ->assertSeeIn("{$riwayat} .tl", 'Lamaran dikirim')
                ->assertSeeIn("{$riwayat} .tl", 'Terpilih sementara')
                ->assertSeeIn("{$riwayat} .tl", 'Masuk daftar pendek');
        });
    }

    public function test_pagination_dapat_dinavigasi(): void
    {
        $kandidat = $this->makeKandidat();
        foreach (range(1, 11) as $i) {
            $this->makeApplicationFor($kandidat, ApplicationStatus::Pending, [
                'title' => sprintf('Dusk-Halaman-%02d', $i),
                'created_at' => sprintf('2026-11-%02d 09:00:00', $i),
            ]);
        }

        $this->browse(function (Browser $browser) use ($kandidat) {
            // Terbaru lebih dulu: halaman 1 berisi Dusk-Halaman-11 s.d. 02, halaman 2 berisi 01.
            $browser->loginAs($kandidat)
                ->visit(route('kandidat.lamaran.index'))
                ->assertSee('Halaman 1 dari 2')
                ->assertSee('Dusk-Halaman-02')
                ->assertDontSee('Dusk-Halaman-01')
                ->click('.la-pager a.btn')
                ->waitForText('Halaman 2 dari 2')
                ->assertQueryStringHas('page', '2')
                ->assertSee('Dusk-Halaman-01')
                ->assertDontSee('Dusk-Halaman-02')
                ->click('.la-pager a.btn')
                ->waitForText('Halaman 1 dari 2')
                ->assertDontSee('Dusk-Halaman-01');
        });
    }

    public function test_keadaan_kosong_menampilkan_tautan_eksplor_proyek(): void
    {
        $kandidat = $this->makeKandidat();

        $this->browse(function (Browser $browser) use ($kandidat) {
            $browser->loginAs($kandidat)
                ->visit(route('kandidat.lamaran.index'))
                ->assertSee('Belum ada lamaran')
                ->click('main .al a')
                ->assertPathIs('/kandidat/proyek');
        });
    }

    public function test_tombol_detail_proyek_membuka_halaman_proyek(): void
    {
        $kandidat = $this->makeKandidat();
        $application = $this->makeApplicationFor($kandidat, ApplicationStatus::Pending, ['title' => 'Dusk Navigasi Detail']);
        $projectId = $application->project_id;

        $this->browse(function (Browser $browser) use ($kandidat, $application, $projectId) {
            $browser->loginAs($kandidat)
                ->visit(route('kandidat.lamaran.index'))
                ->click("#lamaran-{$application->id} a[href\$=\"/kandidat/proyek/{$projectId}\"]")
                ->assertPathIs("/kandidat/proyek/{$projectId}")
                ->assertSee('Dusk Navigasi Detail');
        });
    }
}
