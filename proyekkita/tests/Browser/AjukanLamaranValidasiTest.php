<?php

namespace Tests\Browser;

use App\Enums\ProjectStatus;
use App\Models\Application;
use App\Models\Project;
use Laravel\Dusk\Browser;
use Tests\Concerns\BuildsApplicationScenario;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

/**
 * PBI-16 (FR-09) - interaksi antarmuka yang tidak terjangkau Feature test: pesan error di layar,
 * penghitung karakter (JavaScript), kondisi form nonaktif, dan pesan sukses.
 *
 * Database: lihat Tests\Concerns\UsesDuskTestDatabase (wajib memakai database/dusk.sqlite).
 */
class AjukanLamaranValidasiTest extends DuskTestCase
{
    use BuildsApplicationScenario;
    use UsesDuskTestDatabase;

    public function test_pesan_terlalu_pendek_menampilkan_error_dan_tidak_menyimpan(): void
    {
        $kandidat = $this->makeKandidat();
        $project = $this->makeProject();

        $this->browse(function (Browser $browser) use ($kandidat, $project) {
            $browser->loginAs($kandidat)
                ->visit(route('kandidat.proyek.show', $project))
                ->type('@pesan-lamaran', 'terlalu pendek')
                ->press('@kirim-lamaran')
                ->waitFor('@error-pesan')
                ->assertSeeIn('@error-pesan', 'Pesan lamaran minimal 50 karakter.')
                ->assertInputValue('@pesan-lamaran', 'terlalu pendek')
                ->assertEnabled('@kirim-lamaran');
        });

        $this->assertSame(0, Application::count());
    }

    public function test_penghitung_karakter_diperbarui_saat_mengetik(): void
    {
        $kandidat = $this->makeKandidat();
        $project = $this->makeProject();

        $this->browse(function (Browser $browser) use ($kandidat, $project) {
            $browser->loginAs($kandidat)
                ->visit(route('kandidat.proyek.show', $project))
                ->assertSeeIn('#hitung-pesan', '0 / 50 karakter')
                ->type('@pesan-lamaran', 'abc')
                ->assertSeeIn('#hitung-pesan', '3 / 50 karakter')
                ->type('@pesan-lamaran', str_repeat('a', 50))
                ->assertSeeIn('#hitung-pesan', '50 / 50 karakter');
        });
    }

    public function test_profil_belum_lengkap_menonaktifkan_form_dan_tautan_ke_profil(): void
    {
        $kandidat = $this->makeKandidat(skills: 0);
        $project = $this->makeProject();

        $this->browse(function (Browser $browser) use ($kandidat, $project) {
            $browser->loginAs($kandidat)
                ->visit(route('kandidat.proyek.show', $project))
                ->assertPresent('@profil-belum-lengkap')
                ->assertSeeIn('@profil-belum-lengkap', 'Lengkapi profil sebelum melamar')
                ->assertSeeIn('@profil-belum-lengkap', 'tambahkan minimal 1 keterampilan')
                ->assertDisabled('@pesan-lamaran')
                ->assertDisabled('@kirim-lamaran')
                ->clickLink('Buka Profil & Portofolio')
                ->assertPathIs('/kandidat/profil');
        });

        $this->assertSame(0, Application::count());
    }

    public function test_proyek_ditutup_atau_kedaluwarsa_menonaktifkan_form(): void
    {
        $kandidat = $this->makeKandidat();
        $ditutup = $this->makeProject(['status' => ProjectStatus::Closed]);
        $kedaluwarsa = $this->makeProject(['application_deadline' => now()->subDays(2)->toDateString()]);

        $this->browse(function (Browser $browser) use ($kandidat, $ditutup, $kedaluwarsa) {
            $browser->loginAs($kandidat)
                ->visit(route('kandidat.proyek.show', $ditutup))
                ->assertPresent('@lamaran-tertutup')
                ->assertSeeIn('@lamaran-tertutup', 'Proyek ini sudah ditutup dan tidak lagi menerima lamaran.')
                ->assertDisabled('@pesan-lamaran')
                ->assertDisabled('@kirim-lamaran')
                ->visit(route('kandidat.proyek.show', $kedaluwarsa))
                ->assertPresent('@lamaran-tertutup')
                ->assertSeeIn('@lamaran-tertutup', 'Tenggat lamaran proyek ini sudah berakhir.')
                ->assertDisabled('@pesan-lamaran')
                ->assertDisabled('@kirim-lamaran');
        });

        $this->assertSame(0, Application::count());
    }

    public function test_setelah_mengirim_pesan_sukses_tampil_dan_tombol_menjadi_lamaran_terkirim(): void
    {
        $kandidat = $this->makeKandidat();
        $project = $this->makeProject();

        $this->browse(function (Browser $browser) use ($kandidat, $project) {
            $browser->loginAs($kandidat)
                ->visit(route('kandidat.proyek.show', $project))
                ->type('@pesan-lamaran', $this->validMessage())
                ->press('@kirim-lamaran')
                ->waitFor('@status-lamaran')
                ->assertSee('Lamaran berhasil dikirim dan berstatus Menunggu Keputusan.')
                ->assertSeeIn('@status-lamaran', 'Menunggu Keputusan')
                ->assertSeeIn('@kirim-lamaran', 'Lamaran Terkirim')
                ->assertDisabled('@kirim-lamaran')
                ->assertDisabled('@pesan-lamaran');
        });

        $this->assertSame(1, Application::count());
    }

    public function test_proyek_ditutup_setelah_halaman_dibuka_menampilkan_pesan_error(): void
    {
        $kandidat = $this->makeKandidat();
        $project = $this->makeProject();

        $this->browse(function (Browser $browser) use ($kandidat, $project) {
            $browser->loginAs($kandidat)
                ->visit(route('kandidat.proyek.show', $project))
                ->type('@pesan-lamaran', $this->validMessage());

            // UMKM menutup proyek setelah kandidat membuka halaman dan mengetik pesan.
            Project::whereKey($project->id)->update(['status' => ProjectStatus::Closed->value]);

            $browser->press('@kirim-lamaran')
                ->waitForText('Proyek ini sudah ditutup dan tidak lagi menerima lamaran.')
                ->assertPresent('@lamaran-tertutup')
                ->assertDisabled('@kirim-lamaran');
        });

        $this->assertSame(0, Application::count());
    }
}
