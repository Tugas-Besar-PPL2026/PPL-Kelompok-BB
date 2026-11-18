<?php

namespace Tests\Browser\Adnan\PBI07;

use App\Models\Skill;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PBI07TestCase1Test extends DuskTestCase
{
    public function test_tc_pbi7_001_menampilkan_halaman_data_master_keterampilan(): void
    {
        $skillPertama = Skill::firstOrFail();

        $this->browse(function (Browser $browser) use ($skillPertama) {
            $browser->visit('/login')
                ->type('email', 'admin@proyekkita.test')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->type('password', 'password')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->press('Masuk')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->waitForLocation('/admin/data-master')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->assertVisible('@kartu-keterampilan')
                ->assertSeeIn('@kartu-keterampilan', 'Keterampilan')
                ->assertSeeIn('@ringkasan-keterampilan', 'keterampilan')
                ->assertSeeIn('@kartu-keterampilan', $skillPertama->name);
        });
    }
}