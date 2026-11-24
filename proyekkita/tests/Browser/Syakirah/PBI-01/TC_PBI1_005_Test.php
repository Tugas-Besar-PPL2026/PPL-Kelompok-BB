<?php

namespace Tests\Browser\PBI01;

use App\Enums\VerificationStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TC_PBI1_005_Test extends DuskTestCase
{
    public function test_ubah_nama_usaha_mereset_status_verifikasi(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'umkm.tc005@proyekkita.test'],
            [
                'name'     => 'UMKM TC005 Reset Status',
                'password' => Hash::make('password123'),
                'role'     => 'umkm',
            ]
        );

        $verifiedValue = 'verified';
        if (enum_exists(VerificationStatus::class)) {
            foreach (VerificationStatus::cases() as $case) {
                $val = $case instanceof \BackedEnum ? $case->value : $case->name;
                $haystack = strtolower($case->name . ' ' . $val);
                if (str_contains($haystack, 'verif') || str_contains($haystack, 'approv')) {
                    $verifiedValue = $val;
                    break;
                }
            }
        }

        $profileData = [
            'user_id'        => $user->id,
            'business_name'  => 'Kopi Nusantara Lama',
            'business_field' => 'Kuliner',
            'description'    => 'Deskripsi usaha untuk uji reset status verifikasi.',
            'contact_phone'  => '+62 812-3456-7890',
            'city'           => 'Bandung',
            'address'        => 'Jl. Merdeka No. 25',
            'nib'            => '1234567890123',
        ];

        foreach (['verification_status', 'status_verifikasi', 'status'] as $statusCol) {
            if (Schema::hasColumn('umkm_profiles', $statusCol)) {
                $profileData[$statusCol] = $verifiedValue;
            }
        }

        if (Schema::hasColumn('umkm_profiles', 'updated_at')) {
            $profileData['updated_at'] = now();
        }

        if (DB::table('umkm_profiles')->where('user_id', $user->id)->exists()) {
            DB::table('umkm_profiles')->where('user_id', $user->id)->update($profileData);
        } else {
            if (Schema::hasColumn('umkm_profiles', 'created_at')) {
                $profileData['created_at'] = now();
            }
            DB::table('umkm_profiles')->insert($profileData);
        }

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/umkm/profil')
                ->assertPathIs('/umkm/profil')
                ->waitFor('input[name="business_name"]');

            $defaults = [
                'business_field' => 'Kuliner',
                'description'    => 'Deskripsi usaha untuk uji reset status verifikasi.',
                'contact_phone'  => '+62 812-3456-7890',
                'city'           => 'Bandung',
                'address'        => 'Jl. Merdeka No. 25',
                'nib'            => '1234567890123',
            ];

            foreach ($defaults as $name => $sampleValue) {
                $selector = "input[name=\"{$name}\"], textarea[name=\"{$name}\"]";
                if ($browser->element($selector) !== null && trim((string) $browser->value($selector)) === '') {
                    $browser->type($name, $sampleValue);
                }
            }

            $browser->clear('business_name')
                ->type('business_name', 'Kopi Nusantara Baru')
                ->press('Simpan Perubahan')
                ->assertPathIs('/umkm/profil')
                ->pause(500)
                ->assertSee('Menunggu Pemeriksaan');

            $result = $browser->script('return document.body ? document.body.innerText : "";');
            $bodyText = is_array($result) ? ($result[0] ?? '') : (string) $result;
            $cleanText = preg_replace('/\s+/', ' ', trim($bodyText));

            if (! str_contains($cleanText, 'Status verifikasi kembali menjadi Menunggu Pemeriksaan')) {
                $this->fail(
                    'Pesan reset status tidak muncul persis sesuai ekspektasi. Cuplikan teks halaman: '
                    . mb_substr($cleanText, 250, 1000)
                );
            }

            $this->assertDatabaseHas('umkm_profiles', [
                'user_id'       => $user->id,
                'business_name' => 'Kopi Nusantara Baru',
            ]);
        });
    }
}