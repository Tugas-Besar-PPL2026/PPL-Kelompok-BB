<?php

namespace Tests\Browser;

use App\Enums\ProjectStatus;
use App\Models\Notification;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Concerns\BuildsApplicationScenario;
use Tests\DuskTestCase;
use Tests\Concerns\UsesDuskTestDatabase;

class PengajuanModerasiTest extends DuskTestCase
{
    use BuildsApplicationScenario;
    use UsesDuskTestDatabase;

    public function test_umkm_mengajukan_draf_untuk_moderasi_melalui_browser(): void
    {
        $admin = User::factory()->admin()->create();
        $umkm = $this->makeUmkm();
        $project = $this->makeProject(['status' => ProjectStatus::Draft, 'published_at' => null], $umkm->umkmProfile);

        $this->browse(function (Browser $browser) use ($umkm, $project) {
            $browser->loginAs($umkm)
                ->visit(route('umkm.proyek.review', $project))
                ->assertSee('Syarat Pengajuan')
                ->assertSeeIn('@status-proyek', 'Draf')
                ->assertDisabled('@ajukan-moderasi')
                ->check('@pernyataan')
                ->assertEnabled('@ajukan-moderasi');

            // Gulung ke tombol dulu agar tidak tertutup navbar yang menempel di atas.
            $browser->script("document.querySelector('[dusk=\"ajukan-moderasi\"]').scrollIntoView({block: 'center'});");

            $browser->press('@ajukan-moderasi')
                ->waitForText('Proyek diajukan dan menunggu moderasi admin.', 15)
                ->assertSeeIn('@status-proyek', 'Menunggu Moderasi')
                ->assertPresent('@info-sudah-diajukan')
                ->assertMissing('@ajukan-moderasi');
        });

        $this->assertSame(ProjectStatus::PendingReview, $project->fresh()->status);
        $this->assertTrue(Notification::where('user_id', $admin->id)->where('related_id', $project->id)->exists());
    }

    public function test_tombol_ajukan_nonaktif_jika_syarat_belum_terpenuhi(): void
    {
        $umkm = $this->makeUmkm();
        $project = $this->makeProject(
            ['status' => ProjectStatus::Draft, 'published_at' => null, 'total_compensation' => 3000000],
            $umkm->umkmProfile
        );

        $this->browse(function (Browser $browser) use ($umkm, $project) {
            $browser->loginAs($umkm)
                ->visit(route('umkm.proyek.review', $project))
                ->assertSeeIn('@syarat-terms', 'Rp2.500.000')
                ->assertDisabled('@pernyataan')
                ->assertDisabled('@ajukan-moderasi');
        });

        $this->assertSame(ProjectStatus::Draft, $project->fresh()->status);
    }
}
