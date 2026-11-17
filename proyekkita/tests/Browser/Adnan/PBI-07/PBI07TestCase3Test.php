<?php

namespace Tests\Browser\Adnan\PBI07;

use App\Models\Skill;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PBI07TestCase3Test extends DuskTestCase
{
    public function test_tc_pbi7_003_tambah_nama_satu_karakter_dan_bersimbol(): void
    {
        // Gunakan simbol unik jika R / C++ / .NET sudah ada dari run sebelumnya
        $daftarSkill = ['R', 'C++', '.NET'];

        $this->browse(function (Browser $browser) use (&$daftarSkill) {
            $browser->visit('/login')
                ->type('email', 'admin@proyekkita.test')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->type('password', 'password')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->press('Masuk')
                ->pause((int) env('DUSK_JEDA', 1000))
                ->waitForLocation('/admin/data-master')
                ->pause((int) env('DUSK_JEDA', 1000));

            foreach ($daftarSkill as $index => $nama) {
                if (Skill::whereRaw('LOWER(name) = ?', [mb_strtolower($nama)])->exists()) {
                    $nama = $nama . '_' . date('His') . $index;
                    $daftarSkill[$index] = $nama;
                }

                $browser->type('@input-keterampilan-baru', $nama)
                ->pause((int) env('DUSK_JEDA', 1000))
                    ->click('@tambah-keterampilan')
                ->pause((int) env('DUSK_JEDA', 1000))
                    ->pause((int) env('DUSK_JEDA', 1000))
                    ->assertSeeIn('@kartu-keterampilan', $nama);
            }
        });

        foreach ($daftarSkill as $nama) {
            $this->assertDatabaseHas('skills', [
                'name' => $nama,
                'is_active' => 1,
            ]);
        }
    }
}