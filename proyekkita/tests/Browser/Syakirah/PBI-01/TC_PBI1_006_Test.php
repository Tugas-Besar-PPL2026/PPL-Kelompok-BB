<?php

namespace Tests\Browser\PBI01;

use App\Enums\VerificationStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TC_PBI1_006_Test extends DuskTestCase
{
    public function test_ubah_data_non_legalitas_tanpa_reset_status(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'umkm.terverifikasi@proyekkita.test'],
            [
                'name'     => 'UMKM Terverifikasi Test',
                'password' => Hash::make('password123'),
                'role'     => 'umkm',
            ]
        );

        // Ambil nilai backing value yang valid dari enum App\Enums\VerificationStatus
        $verifiedValue = 'verified';
        if (enum_exists(VerificationStatus::class)) {
            $cases = VerificationStatus::cases();
            foreach ($cases as $case) {
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
            'business_name'  => 'Usaha Terverifikasi',
            'business_field' => 'Kuliner',
            'description'    => 'Deskripsi awal usaha terverifikasi.',
            'contact_phone'  => '+62 812-3456-7890',
            'city'           => 'Bandung',
            'address'        => 'Jl. Asia Afrika No. 1',
            'nib'            => '1234567890123',
        ];

        foreach (['verification_status', 'status_verifikasi', 'status'] as $statusCol) {
            if (Schema::hasColumn('umkm_profiles', $statusCol)) {
                $profileData[$statusCol] = $verifiedValue;
            }
        }

        foreach (['is_verified', 'verified'] as $boolCol) {
            if (Schema::hasColumn('umkm_profiles', $boolCol)) {
                $profileData[$boolCol] = true;
            }
        }

        if (Schema::hasColumn('umkm_profiles', 'updated_at')) {
            $profileData['updated_at'] = now();
        }

        $existing = DB::table('umkm_profiles')->where('user_id', $user->id)->first();
        if ($existing) {
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
                ->waitFor('input[name="city"]')
                ->clear('city')
                ->type('city', 'Kota Bandung')
                ->clear('address')
                ->type('address', 'Jl. Dipatiukur No. 10')
                ->clear('description')
                ->type('description', 'Pembaruan deskripsi usaha non-legalitas')
                ->press('Simpan Perubahan')
                ->assertPathIs('/umkm/profil')
                ->assertSee('Terverifikasi')
                ->assertDontSee('Status verifikasi kembali menjadi Menunggu Pemeriksaan.');

            $this->assertDatabaseHas('umkm_profiles', [
                'user_id'     => $user->id,
                'city'        => 'Kota Bandung',
                'address'     => 'Jl. Dipatiukur No. 10',
                'description' => 'Pembaruan deskripsi usaha non-legalitas',
            ]);
        });
    }
}