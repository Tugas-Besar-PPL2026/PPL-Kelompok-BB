<?php

namespace Tests\Browser\Adnan\PBI07;

use App\Models\Skill;
use Laravel\Dusk\Browser;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

class PBI07TestCase10Test extends DuskTestCase
{
    use UsesDuskTestDatabase;

    public function test_tc_pbi7_010_ubah_nama_jadi_nama_keterampilan_lain(): void
    {
        $skills = Skill::orderBy('id')->take(2)->get();
        $skillSatu = $skills[0];
        $skillDua = $skills[1];

        $this->browse(function (Browser $browser) use ($skillSatu, $skillDua) {
            $browser->visit('/login')
                ->type('email', 'admin@proyekkita.test')
                ->type('password', 'password')
                ->press('Masuk')
                ->waitForLocation('/admin/data-master')
                ->click("@ubah-keterampilan-{$skillDua->id}")
                ->waitFor("@input-ubah-keterampilan-{$skillDua->id}")
                ->clear("@input-ubah-keterampilan-{$skillDua->id}")
                ->type("@input-ubah-keterampilan-{$skillDua->id}", $skillSatu->name)
                ->click("@simpan-ubah-keterampilan-{$skillDua->id}")
                ->assertVisible("@galat-ubah-keterampilan-{$skillDua->id}")
                ->assertSeeIn("@galat-ubah-keterampilan-{$skillDua->id}", 'sudah terdaftar');
        });

        $this->assertDatabaseHas('skills', [
            'id' => $skillDua->id,
            'name' => $skillDua->name,
        ]);
    }
}