<?php

namespace Tests\Browser\Adnan\PBI07;

use App\Models\Skill;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PBI07TestCase11Test extends DuskTestCase
{
    public function test_tc_pbi7_011_simpan_edit_tanpa_mengubah_nama(): void
    {
        $skill = Skill::orderBy('id')->firstOrFail();

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
                ->click("@ubah-keterampilan-{$skill->id}")
                ->pause((int) env('DUSK_JEDA', 1000))
                ->waitFor("@simpan-ubah-keterampilan-{$skill->id}")
                ->click("@simpan-ubah-keterampilan-{$skill->id}")
                ->pause((int) env('DUSK_JEDA', 1000))
                ->pause((int) env('DUSK_JEDA', 1000))
                ->assertMissing("@galat-ubah-keterampilan-{$skill->id}")
                ->assertSeeIn("@nama-keterampilan-{$skill->id}", $skill->name);
        });

        $this->assertDatabaseHas('skills', [
            'id' => $skill->id,
            'name' => $skill->name,
        ]);
    }
}