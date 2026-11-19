<?php

namespace Tests\Browser\PBI02;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TC_PBI2_007_Test extends DuskTestCase
{
    public function test_headline_81_karakter_ditolak(): void
    {
        $userData = [
            'name'     => 'Kandidat Headline 81',
            'password' => Hash::make('password123'),
        ];
        if (Schema::hasColumn('users', 'role')) {
            $userData['role'] = 'kandidat';
        }

        $kandidat = User::firstOrCreate(
            ['email' => 'kandidat@proyekkita.test'],
            $userData
        );

        $headline81 = str_repeat('a', 81);

        $this->browse(function (Browser $browser) use ($kandidat, $headline81) {
            $browser->loginAs($kandidat)
                ->visit('/kandidat/profil/data-diri')
                ->assertPathIs('/kandidat/profil/data-diri')
                ->waitFor('input[name="headline"]');

            $defaults = [
                'full_name'  => 'Kandidat Headline 81',
                'domicile'   => 'Kota Bandung',
                'domisili'   => 'Kota Bandung',
                'city'       => 'Kota Bandung',
                'university' => 'Telkom University',
                'major'      => 'S1 Informatika',
                'summary'    => 'Ringkasan singkat profil kandidat untuk uji validasi 81 karakter.',
            ];

            foreach ($defaults as $name => $sampleValue) {
                $selector = "input[name=\"{$name}\"], textarea[name=\"{$name}\"], select[name=\"{$name}\"]";
                if ($browser->element($selector) !== null && trim((string) $browser->value($selector)) === '') {
                    if ($browser->element("select[name=\"{$name}\"]") !== null) {
                        $browser->select($name, $sampleValue);
                    } else {
                        $browser->type($name, $sampleValue);
                    }
                }
            }

            $browser->clear('headline')
                ->type('headline', $headline81)
                ->press('Simpan Profil')
                ->pause(600);

            $result = $browser->script('return document.body ? document.body.innerText : "";');
            $bodyText = is_array($result) ? ($result[0] ?? '') : (string) $result;
            $cleanText = preg_replace('/\s+/', ' ', trim($bodyText));

            if (! str_contains($cleanText, '80 karakter') && ! str_contains($cleanText, 'Headline maksimal 80 karakter')) {
                $this->fail(
                    'Pesan penolakan headline 81 karakter tidak ditemukan. Cuplikan halaman: '
                    . mb_substr($cleanText, 200, 1000)
                );
            }
        });
    }
}