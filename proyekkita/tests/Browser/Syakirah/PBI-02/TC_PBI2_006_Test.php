<?php

namespace Tests\Browser\PBI02;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TC_PBI2_006_Test extends DuskTestCase
{
    public function test_headline_tepat_80_karakter(): void
    {
        $userData = [
            'name'     => 'Kandidat Headline 80',
            'password' => Hash::make('password123'),
        ];

        if (Schema::hasColumn('users', 'role')) {
            $userData['role'] = 'kandidat';
        }

        $kandidat = User::firstOrCreate(
            ['email' => 'kandidat@proyekkita.test'],
            $userData
        );

        if (Schema::hasColumn('users', 'role') && $kandidat->role !== 'kandidat') {
            $kandidat->update(['role' => 'kandidat']);
        }

        $headline80 = str_repeat('a', 80);

        $this->browse(function (Browser $browser) use ($kandidat, $headline80) {
            $browser->loginAs($kandidat)
                ->visit('/kandidat/profil/data-diri')
                ->assertPathIs('/kandidat/profil/data-diri')
                ->waitFor('input[name="headline"]')
                ->pause(300);

            // Isi field wajib lain jika masih kosong agar form tidak tertahan validasi
            $defaults = [
                'full_name'  => 'Kandidat Headline 80',
                'domicile'   => 'Kota Bandung',
                'domisili'   => 'Kota Bandung',
                'city'       => 'Kota Bandung',
                'university' => 'Telkom University',
                'major'      => 'S1 Informatika',
                'summary'    => 'Ringkasan singkat profil kandidat untuk pengujian.',
            ];

            foreach ($defaults as $name => $sampleValue) {
                $selector = "input[name=\"{$name}\"], textarea[name=\"{$name}\"], select[name=\"{$name}\"]";
                if ($browser->element($selector) !== null) {
                    $currentVal = (string) $browser->value($selector);
                    if (trim($currentVal) === '') {
                        if ($browser->element("select[name=\"{$name}\"]") !== null) {
                            $browser->select($name, $sampleValue);
                        } else {
                            $browser->type($name, $sampleValue);
                        }
                    }
                }
            }

            $browser->waitFor('input[name="headline"]')
                ->clear('headline')
                ->type('headline', $headline80)
                ->pause(300)
                ->press('Simpan Profil')
                ->pause(700)
                ->waitFor('input[name="headline"]')
                ->assertInputValue('headline', $headline80);
        });
    }
}