<?php

namespace Tests\Browser\Adnan\PBI07;

use App\Models\Skill;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PBI07TestCase10Test extends DuskTestCase
{
    public function test_tc_pbi7_010_ubah_nama_jadi_nama_keterampilan_lain(): void
    {
        $skills = Skill::orderBy('id')->take(2)->get();
        $skillSatu = $skills[0];
        $skillDua = $skills[1];

        $this->browse(function (Browser $browser) use ($skillSatu, $skillDua) {
            $browser->visit('/login')
                ->type('email', 'admin@proyekkita.test')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->type('password', 'password')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->press('Masuk')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->waitForLocation('/admin/data-master')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->click("@ubah-keterampilan-{$skillDua->id}")
                ->pause((int) env('DUSK_JEDA', 1000))
                ->waitFor("@input-ubah-keterampilan-{$skillDua->id}")
                ->clear("@input-ubah-keterampilan-{$skillDua->id}")
                ->type("@input-ubah-keterampilan-{$skillDua->id}", $skillSatu->name)
                ->pause((int) env('DUSK_JEDA', 1000))
                ->click("@simpan-ubah-keterampilan-{$skillDua->id}")
                ->pause((int) env('DUSK_JEDA', 1000))
                ->pause((int) env('DUSK_JEDA', 1000))
                ->assertVisible("@galat-ubah-keterampilan-{$skillDua->id}")
                ->assertSeeIn("@galat-ubah-keterampilan-{$skillDua->id}", 'sudah terdaftar');
        });

        $this->assertDatabaseHas('skills', [
            'id' => $skillDua->id,
            'name' => $skillDua->name,
        ]);
    }
}