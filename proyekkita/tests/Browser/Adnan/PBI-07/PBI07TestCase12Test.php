<?php

namespace Tests\Browser\Adnan\PBI07;

use App\Models\Skill;
use Laravel\Dusk\Browser;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

class PBI07TestCase12Test extends DuskTestCase
{
    use UsesDuskTestDatabase;

    public function test_tc_pbi7_012_menonaktifkan_keterampilan(): void
    {
        $skill = Skill::where('is_active', true)->firstOrFail();

        $this->browse(function (Browser $browser) use ($skill) {
            $browser->visit('/login')
                ->type('email', 'admin@proyekkita.test')
                ->type('password', 'password')
                ->press('Masuk')
                ->waitForLocation('/admin/data-master')
                ->assertSeeIn("@status-keterampilan-{$skill->id}", 'Aktif')
                ->click("@toggle-keterampilan-{$skill->id}")
                ->assertSeeIn("@status-keterampilan-{$skill->id}", 'Nonaktif');
        });

        $this->assertDatabaseHas('skills', [
            'id' => $skill->id,
            'is_active' => 0,
        ]);
    }
}