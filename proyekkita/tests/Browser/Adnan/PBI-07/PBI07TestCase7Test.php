<?php

namespace Tests\Browser\Adnan\PBI07;

use App\Models\Skill;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PBI07TestCase7Test extends DuskTestCase
{
    public function test_tc_pbi7_007_duplikat_dengan_keterampilan_nonaktif(): void
    {
        $skillNonaktif = Skill::where('is_active', false)->first();

        $this->browse(function (Browser $browser) use (&$skillNonaktif) {
            $browser->visit('/login')
                ->type('email', 'admin@proyekkita.test')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->type('password', 'password')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->press('Masuk')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->waitForLocation('/admin/data-master')
                ->pause((int) env('DUSK_JEDA', 1000));

            // Jika belum ada skill nonaktif, nonaktifkan 1 skill melalui UI terlebih dahulu
            if (! $skillNonaktif) {
                $skillNonaktif = Skill::where('is_active', true)->orderByDesc('id')->firstOrFail();
                $browser->click("@toggle-keterampilan-{$skillNonaktif->id}")
                ->pause((int) env('DUSK_JEDA', 1000))
                    ->pause((int) env('DUSK_JEDA', 1000));
            }

            $jumlahSebelumInput = Skill::count();

            $browser->type('@input-keterampilan-baru', $skillNonaktif->name)
                ->pause((int) env('DUSK_JEDA', 1000))
                ->assertVisible('@galat-tambah-keterampilan')
                ->assertSeeIn('@galat-tambah-keterampilan', 'sudah terdaftar')
                ->assertDisabled('@tambah-keterampilan');

            $this->assertEquals($jumlahSebelumInput, Skill::count());
        });
    }
}