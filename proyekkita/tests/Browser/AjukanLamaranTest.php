<?php

namespace Tests\Browser;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use Laravel\Dusk\Browser;
use Tests\Concerns\BuildsApplicationScenario;
use Tests\DuskTestCase;

class AjukanLamaranTest extends DuskTestCase
{
    use BuildsApplicationScenario;

    public function test_kandidat_berhasil_mengirim_lamaran_melalui_browser(): void
    {
        // Siapkan kandidat dan proyek untuk pengujian.
        $kandidat = $this->makeKandidat();
        $project = $this->makeProject();
        $pesan = $this->validMessage();

        // Jalankan interaksi melalui browser Chrome.
        $this->browse(function (Browser $browser) use ($kandidat, $project, $pesan) {
            $browser->loginAs($kandidat)
                ->visit(route('kandidat.proyek.show', $project));

            
            $browser->assertSee('Ajukan Lamaran')
                ->assertEnabled('@pesan-lamaran')
                ->type('@pesan-lamaran', $pesan)
                ->press('@kirim-lamaran')
                ->waitFor('@status-lamaran')
                ->assertSeeIn('@status-lamaran', 'Lamaran Anda sudah terkirim');
        });

        // Pastikan lamaran benar-benar tersimpan di database.
        $this->assertDatabaseHas('applications', [
            'project_id' => $project->id,
            'candidate_profile_id' => $kandidat->candidateProfile->id,
            'message' => $pesan,
            'status' => ApplicationStatus::Pending->value,
        ]);

        $this->assertSame(1, Application::where('project_id', $project->id)->count());
    }
}