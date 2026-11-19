<?php

namespace Tests\Browser\PBI02;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TC_PBI2_005_Test extends DuskTestCase
{
    public function test_semua_kolom_wajib_kosong(): void
    {
        $userData = [
            'name'     => 'Kandidat Validasi Kosong',
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

        $this->browse(function (Browser $browser) use ($kandidat) {
            $browser->loginAs($kandidat)
                ->visit('/kandidat/profil/data-diri')
                ->assertPathIs('/kandidat/profil/data-diri')
                ->waitFor('input[name="full_name"], input[name="headline"]');

            $clearIfExists = function (string $name) use ($browser): void {
                $selector = "input[name=\"{$name}\"], textarea[name=\"{$name}\"]";
                if ($browser->element($selector) !== null) {
                    $browser->clear($name);
                }
            };

            foreach (['full_name', 'domicile', 'domisili', 'city', 'headline', 'university', 'major', 'summary'] as $field) {
                $clearIfExists($field);
            }

            $browser->press('Simpan Profil')
                ->pause(600)
                ->assertSee('Nama lengkap wajib diisi.')
                ->assertSee('Domisili wajib diisi.')
                ->assertSee('Headline wajib diisi.')
                ->assertSee('Universitas wajib diisi.')
                ->assertSee('Program studi wajib diisi.')
                ->assertSee('Ringkasan diri wajib diisi.');
        });
    }
}