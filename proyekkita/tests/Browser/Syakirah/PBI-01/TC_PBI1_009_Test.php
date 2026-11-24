<?php

namespace Tests\Browser\PBI01;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TC_PBI1_009_Test extends DuskTestCase
{
    public function test_akses_profil_usaha_oleh_kandidat_ditolak_403(): void
    {
        $userData = [
            'name'     => 'Kandidat Test',
            'password' => Hash::make('password123'),
        ];

        if (Schema::hasColumn('users', 'role')) {
            $userData['role'] = 'kandidat';
        }

        $kandidat = User::firstOrCreate(
            ['email' => 'kandidat@proyekkita.test'],
            $userData
        );

        // Jika model User memakai relasi/kolom role atau spatie/permission, pastikan rolenya kandidat
        if (Schema::hasColumn('users', 'role') && $kandidat->role !== 'kandidat') {
            $kandidat->update(['role' => 'kandidat']);
        }

        $this->browse(function (Browser $browser) use ($kandidat) {
            $browser->loginAs($kandidat)
                ->visit('/umkm/profil')
                ->pause(500);

            $result = $browser->script('return document.body ? document.body.innerText : "";');
            $bodyText = is_array($result) ? ($result[0] ?? '') : (string) $result;
            $cleanText = preg_replace('/\s+/', ' ', trim($bodyText));

            if (! str_contains($cleanText, '403') && ! str_contains(strtolower($cleanText), 'forbidden')) {
                $this->fail(
                    'Akses kandidat ke /umkm/profil tidak mengembalikan 403. Cuplikan halaman: '
                    . mb_substr($cleanText, 0, 800)
                );
            }

            $browser->assertSee('403');
        });
    }
}