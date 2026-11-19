<?php

namespace Tests\Browser\PBI01;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TC_PBI1_004_Test extends DuskTestCase
{
    public function test_buat_profil_usaha_pertama_kali(): void
    {
        $email = 'umkm.baru@proyekkita.test';

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'UMKM Profil Baru Test',
                'password' => Hash::make('password123'),
                'role' => 'umkm',
            ]
        );

        // Bersihkan profil usaha yang mungkin tersisa dari run sebelumnya
        // agar skenario "pertama kali" selalu konsisten (sesuaikan relasi jika berbeda)
        foreach (['umkmProfile', 'profile', 'business', 'umkm'] as $relation) {
            if (method_exists($user, $relation) && $user->{$relation}) {
                $user->{$relation}->delete();
            }
        }

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/umkm/profil')
                ->assertPathIs('/umkm/profil')
                ->waitFor('input[name="business_name"]')
                ->waitFor('input[name="contact_phone"]')
                ->type('business_name', 'Kopi Nusantara Jaya')
                ->type('business_field', 'Kuliner')
                ->type('description', 'Usaha kedai kopi dan produk olahan lokal.')
                ->type('contact_phone', '+62 812-3456-7890')
                ->type('city', 'Bandung')
                ->type('address', 'Jl. Riau No. 45')
                ->type('nib', '1234567890123')
                ->press('Simpan Perubahan')
                ->assertPathIs('/umkm/profil')
                ->pause(600);

            $result = $browser->script('return document.body.innerText;');
            $bodyText = is_array($result) ? ($result[0] ?? '') : (string) $result;
            $cleanText = preg_replace('/\s+/', ' ', trim($bodyText));

            if (! str_contains($cleanText, 'Profil usaha berhasil disimpan')) {
                $this->fail(
                    'Pembuatan profil pertama kali belum menampilkan pesan sukses. Cuplikan halaman: '
                    . mb_substr($cleanText, 250, 1200)
                );
            }

            $browser->assertInputValue('business_name', 'Kopi Nusantara Jaya')
                ->assertInputValue('contact_phone', '+62 812-3456-7890')
                ->assertInputValue('city', 'Bandung');
        });
    }
}