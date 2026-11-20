<?php

namespace Tests\Browser\Adnan\PBI07;

use App\Models\Skill;
use Laravel\Dusk\Browser;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

class PBI07TestCase11Test extends DuskTestCase
{
    use UsesDuskTestDatabase;

    public function test_tc_pbi7_011_simpan_edit_tanpa_mengubah_nama(): void
    {
        $skill = Skill::orderBy('id')->firstOrFail();

        $this->browse(function (Browser $browser) use ($skill) {
            $browser->visit('/login')
                ->type('email', 'admin@proyekkita.test')
                ->type('password', 'password')
                ->press('Masuk')
                ->waitForLocation('/admin/data-master')
                ->click("@ubah-keterampilan-{$skill->id}")
                ->waitFor("@simpan-ubah-keterampilan-{$skill->id}")
                ->click("@simpan-ubah-keterampilan-{$skill->id}")
                ->assertMissing("@galat-ubah-keterampilan-{$skill->id}")
                ->assertSeeIn("@nama-keterampilan-{$skill->id}", $skill->name);
        });

        $this->assertDatabaseHas('skills', [
            'id' => $skill->id,
            'name' => $skill->name,
        ]);
    }
}