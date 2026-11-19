<?php

namespace Tests\Browser\PBI02;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TC_PBI2_003_Test extends DuskTestCase
{
    public function test_perbarui_data_diri_valid(): void
    {
        $userData = [
            'name'     => 'Kandidat Update Test',
            'password' => Hash::make('password123'),
        ];
        if (Schema::hasColumn('users', 'role')) {
            $userData['role'] = 'kandidat';
        }

        $kandidat = User::firstOrCreate(
            ['email' => 'kandidat.tc203@proyekkita.test'],
            $userData
        );

        $this->browse(function (Browser $browser) use ($kandidat) {
            $browser->loginAs($kandidat)
                ->visit('/kandidat/profil/data-diri')
                ->assertPathIs('/kandidat/profil/data-diri')
                ->waitFor('input[name="university"]')
                ->waitFor('input[name="headline"]');

            $defaults = [
                'full_name' => 'Kandidat Update Test',
                'city'      => 'Kota Bandung',
                'domicile'  => 'Kota Bandung',
                'domisili'  => 'Kota Bandung',
                'major'     => 'S1 Desain Produk',
                'summary'   => 'Ringkasan data diri kandidat yang valid untuk pembaruan profil.',
                'phone'     => '081234567890',
                'bio'       => 'Ringkasan data diri kandidat yang valid.',
            ];

            foreach ($defaults as $name => $sampleVal) {
                $sel = "input[name=\"{$name}\"], textarea[name=\"{$name}\"], select[name=\"{$name}\"]";
                if ($browser->element($sel) !== null && trim((string) $browser->value($sel)) === '') {
                    if ($browser->element("select[name=\"{$name}\"]") !== null) {
                        $browser->select($name, $sampleVal);
                    } else {
                        $browser->type($name, $sampleVal);
                    }
                }
            }

            $browser->clear('university')
                ->type('university', 'Telkom University Bandung')
                ->clear('headline')
                ->type('headline', 'Desainer UI untuk UMKM')
                ->press('Simpan Profil')
                ->pause(600);

            $result = $browser->script('return document.body ? document.body.innerText : "";');
            $bodyText = is_array($result) ? ($result[0] ?? '') : (string) $result;
            $cleanText = preg_replace('/\s+/', ' ', trim($bodyText));

            if (! str_contains($cleanText, 'Data diri berhasil disimpan')) {
                $this->fail(
                    'Pesan simpan data diri belum muncul. Cuplikan halaman: '
                    . mb_substr($cleanText, 200, 1000)
                );
            }

            $browser->assertInputValue('university', 'Telkom University Bandung')
                ->assertInputValue('headline', 'Desainer UI untuk UMKM');

            $this->assertDatabaseCount('candidate_profiles', User::has('candidateProfile')->count());
        });
    }
}