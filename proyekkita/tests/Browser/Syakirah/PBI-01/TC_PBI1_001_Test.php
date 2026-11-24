<?php

namespace Tests\Browser\PBI01;

use App\Enums\VerificationStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TC_PBI1_001_Test extends DuskTestCase
{
    public function test_tampilkan_profil_usaha_terverifikasi(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'umkm.tc001@proyekkita.test'],
            [
                'name'     => 'UMKM TC001 Terverifikasi',
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
            'business_name'  => 'Usaha Terverifikasi 001',
            'business_field' => 'Kuliner',
            'description'    => 'Deskripsi profil usaha terverifikasi.',
            'contact_phone'  => '+62 812-3456-7890',
            'city'           => 'Bandung',
            'address'        => 'Jl. Braga No. 12',
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
                ->assertSee('Profil Usaha')
                ->assertSee('Terverifikasi')
                ->assertSee('Rata-rata Penilaian');
        });
    }
}