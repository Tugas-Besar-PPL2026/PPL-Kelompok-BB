<?php

namespace Tests\Browser\Adnan\PBI07;

use App\Models\Skill;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PBI07TestCase9Test extends DuskTestCase
{
    public function test_tc_pbi7_009_ubah_nama_keterampilan(): void
    {
        $skills = Skill::orderBy('id')->take(3)->get();
        $targetSkill = $skills->last();
        $namaBaru = 'Canva Pro ' . date('His');

        $this->browse(function (Browser $browser) use ($targetSkill, $namaBaru) {
            $browser->visit('/login')
                ->type('email', 'admin@proyekkita.test')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->type('password', 'password')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->press('Masuk')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->waitForLocation('/admin/data-master')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->click("@ubah-keterampilan-{$targetSkill->id}")
                ->pause((int) env('DUSK_JEDA', 1000))
                ->waitFor("@input-ubah-keterampilan-{$targetSkill->id}")
                ->clear("@input-ubah-keterampilan-{$targetSkill->id}")
                ->type("@input-ubah-keterampilan-{$targetSkill->id}", $namaBaru)
                ->pause((int) env('DUSK_JEDA', 1000))
                ->click("@simpan-ubah-keterampilan-{$targetSkill->id}")
                ->pause((int) env('DUSK_JEDA', 1000))
                ->pause((int) env('DUSK_JEDA', 1000))
                ->assertSeeIn("@nama-keterampilan-{$targetSkill->id}", $namaBaru);
        });

        $this->assertDatabaseHas('skills', [
            'id' => $targetSkill->id,
            'name' => $namaBaru,
        ]);
    }
}