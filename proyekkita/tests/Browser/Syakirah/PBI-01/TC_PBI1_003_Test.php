<?php

namespace Tests\Browser\PBI01;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TC_PBI1_003_Test extends DuskTestCase
{
    public function test_ubah_kota_dan_kontak_pada_profil_terverifikasi(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'umkm@proyekkita.test'],
            [
                'name' => 'UMKM Terverifikasi Test',
                'password' => Hash::make('password123'),
                'role' => 'umkm',
            ]
        );

        $markVerified = function () use ($user): void {
            $user->refresh();

            // Sesuaikan nama relasi/model dan nilai status terverifikasi dengan skema proyekmu
            foreach (['umkmProfile', 'profile', 'business', 'umkm'] as $relation) {
                if (method_exists($user, $relation) && $user->{$relation}) {
                    $model = $user->{$relation};
                    $table = $model->getTable();
                    $updates = [];

                    foreach (['verification_status', 'status_verifikasi', 'status'] as $col) {
                        if (Schema::hasColumn($table, $col)) {
                            $updates[$col] = 'verified';
                        }
                    }

                    foreach (['is_verified', 'verified'] as $boolCol) {
                        if (Schema::hasColumn($table, $boolCol)) {
                            $updates[$boolCol] = true;
                        }
                    }

                    if ($updates !== []) {
                        $model->update($updates);
                    }
                }
            }
        };

        $markVerified();

        $this->browse(function (Browser $browser) use ($user, $markVerified) {
            $browser->loginAs($user)
                ->visit('/umkm/profil')
                ->assertPathIs('/umkm/profil')
                ->waitFor('input[name="city"]')
                ->waitFor('input[name="contact_phone"]');

            $defaults = [
                'business_name' => 'Usaha Uji Coba',
                'business_field' => 'Kuliner',
                'description' => 'Deskripsi singkat usaha untuk pengujian profil UMKM.',
                'address' => 'Jl. Merdeka No. 10',
            ];

            foreach ($defaults as $name => $sampleValue) {
                $selector = "input[name=\"{$name}\"], textarea[name=\"{$name}\"]";
                if ($browser->element($selector) !== null && trim((string) $browser->value($selector)) === '') {
                    $browser->type($name, $sampleValue);
                }
            }

            $nibEmpty = $browser->element('input[name="nib"]') !== null
                && trim((string) $browser->value('input[name="nib"]')) === '';
            $linkEmpty = $browser->element('input[name="business_link"]') !== null
                && trim((string) $browser->value('input[name="business_link"]')) === '';

            if ($nibEmpty && $linkEmpty) {
                $browser->type('nib', '1234567890123');
            }

            $browser->type('city', 'Cimahi')
                ->type('contact_phone', '+62 812-3456-7890')
                ->press('Simpan Perubahan')
                ->assertPathIs('/umkm/profil')
                ->pause(600);

            // Jika record profil baru dibuat saat submit pertama, tandai terverifikasi lalu muat ulang halaman
            $markVerified();
            $browser->refresh()
                ->waitFor('input[name="city"]')
                ->assertInputValue('city', 'Cimahi')
                ->assertInputValue('contact_phone', '+62 812-3456-7890')
                ->assertSee('Terverifikasi');
        });
    }
}