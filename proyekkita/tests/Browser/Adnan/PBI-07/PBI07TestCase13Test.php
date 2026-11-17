<?php

namespace Tests\Browser\Adnan\PBI07;

use App\Models\Skill;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PBI07TestCase13Test extends DuskTestCase
{
    public function test_tc_pbi7_013_mengaktifkan_kembali_keterampilan(): void
    {
        $skill = Skill::where('is_active', false)->firstOrFail();

        $this->browse(function (Browser $browser) use ($skill) {
            $browser->visit('/login')
                ->type('email', 'admin@proyekkita.test')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->type('password', 'password')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->press('Masuk')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->waitForLocation('/admin/data-master')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->assertSeeIn("@status-keterampilan-{$skill->id}", 'Nonaktif')
                ->click("@toggle-keterampilan-{$skill->id}")
                ->pause((int) env('DUSK_JEDA', 1000))
                ->pause((int) env('DUSK_JEDA', 1000))
                ->assertSeeIn("@status-keterampilan-{$skill->id}", 'Aktif');
        });

        $this->assertDatabaseHas('skills', [
            'id' => $skill->id,
            'is_active' => 1,
        ]);
    }
}