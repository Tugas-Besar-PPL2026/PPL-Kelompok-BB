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
                ->type('password', 'password')
                ->press('Masuk')
                ->waitForLocation('/admin/data-master')
                ->assertSeeIn("@status-keterampilan-{$skill->id}", 'Nonaktif')
                ->click("@toggle-keterampilan-{$skill->id}")
                ->pause(500)
                ->assertSeeIn("@status-keterampilan-{$skill->id}", 'Aktif');
        });

        $this->assertDatabaseHas('skills', [
            'id' => $skill->id,
            'is_active' => 1,
        ]);
    }
}