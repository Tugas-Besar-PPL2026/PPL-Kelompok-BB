<?php

namespace Tests\Browser;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use Laravel\Dusk\Browser;
use Tests\Concerns\BuildsApplicationScenario;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

/**
 * PBI-16 + PBI-17 + PBI-18 - alur menyeluruh kandidat:
 * Eksplor Proyek -> Lihat Detail -> kirim lamaran -> Lamaran Saya -> batalkan lewat dialog -> lamar ulang tertutup.
 * Sesuai relasi use case proposal: "Ajukan Lamaran" selalu dicapai lewat pencarian proyek.
 *
 * Database: lihat Tests\Concerns\UsesDuskTestDatabase (wajib memakai database/dusk.sqlite).
 */
class AlurLamaranLengkapTest extends DuskTestCase
{
    use BuildsApplicationScenario;
    use UsesDuskTestDatabase;

    public function test_alur_eksplor_melamar_memantau_lalu_membatalkan(): void
    {
        $kandidat = $this->makeKandidat();
        $project = $this->makeProject(['title' => 'Dusk Alur Lengkap']);

        $this->browse(function (Browser $browser) use ($kandidat, $project) {
            // PBI-16: dari daftar proyek ke detail, lalu kirim lamaran.
            $browser->loginAs($kandidat)
                ->visit(route('kandidat.proyek.index'))
                ->assertSee('Dusk Alur Lengkap')
                ->click('@lihat-detail')
                ->assertPathIs("/kandidat/proyek/{$project->id}")
                ->type('@pesan-lamaran', $this->validMessage())
                ->press('@kirim-lamaran')
                ->waitFor('@status-lamaran')
                ->assertSee('Lamaran berhasil dikirim dan berstatus Menunggu Keputusan.');

            $id = Application::where('project_id', $project->id)->value('id');

            // PBI-17: lamaran tampil di Lamaran Saya lewat menu samping.
            $browser->click('a.mi[href$="/kandidat/lamaran"]')
                ->assertPathIs('/kandidat/lamaran')
                ->assertSeeIn("#lamaran-{$id}", 'Dusk Alur Lengkap')
                ->assertSeeIn("#lamaran-{$id}", 'Menunggu Keputusan');

            // PBI-18: batalkan lewat dialog konfirmasi.
            $browser->click("@batalkan-lamaran-{$id}")
                ->waitFor("@dialog-batal-{$id}")
                ->press("@konfirmasi-batal-{$id}")
                ->waitForText('Lamaran berhasil dibatalkan')
                ->assertSeeIn("#lamaran-{$id}", 'Dibatalkan')
                ->assertMissing("@batalkan-lamaran-{$id}");

            // PBI-16 x PBI-18: pembatalan bersifat final, lamar ulang tidak tersedia.
            $browser->visit(route('kandidat.proyek.show', $project))
                ->assertSeeIn('@status-lamaran', 'Lamaran Anda sudah dibatalkan')
                ->assertDisabled('@pesan-lamaran')
                ->assertDisabled('@kirim-lamaran');
        });

        $application = Application::sole();
        $this->assertSame(ApplicationStatus::Cancelled, $application->status);
        $this->assertSame(2, ApplicationStatusHistory::where('application_id', $application->id)->count());
    }
}
