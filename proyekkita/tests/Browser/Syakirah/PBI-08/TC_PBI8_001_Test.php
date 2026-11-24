<?php

namespace Tests\Browser\PBI08;

use App\Enums\VerificationStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TC_PBI8_001_Test extends DuskTestCase
{
    public function test_buka_antrean_menunggu(): void
    {
        $adminData = [
            'name'     => 'Admin Verifikasi Test',
            'password' => Hash::make('password123'),
        ];
        if (Schema::hasColumn('users', 'role')) {
            $adminData['role'] = 'admin';
        }

        $admin = User::firstOrCreate(
            ['email' => 'admin.verifikasi@proyekkita.test'],
            $adminData
        );

        if (Schema::hasColumn('users', 'role') && $admin->role !== 'admin') {
            $admin->update(['role' => 'admin']);
        }

        $ownerData = [
            'name'     => 'Pemilik Laundry Bersih Kilat',
            'password' => Hash::make('password123'),
        ];
        if (Schema::hasColumn('users', 'role')) {
            $ownerData['role'] = 'umkm';
        }

        $owner = User::firstOrCreate(
            ['email' => 'laundry.kilat@proyekkita.test'],
            $ownerData
        );

        // Ambil backing value status menunggu dari enum VerificationStatus bila ada
        $pendingValue = 'pending';
        if (enum_exists(VerificationStatus::class)) {
            foreach (VerificationStatus::cases() as $case) {
                $val = $case instanceof \BackedEnum ? $case->value : $case->name;
                $haystack = strtolower($case->name . ' ' . $val);
                if (str_contains($haystack, 'pend') || str_contains($haystack, 'wait') || str_contains($haystack, 'menunggu')) {
                    $pendingValue = $val;
                    break;
                }
            }
        }

        $profileData = [
            'user_id'        => $owner->id,
            'business_name'  => 'Laundry Bersih Kilat',
            'business_field' => 'Jasa',
            'description'    => 'Layanan cuci dan setrika kilat untuk pelanggan.',
            'contact_phone'  => '+62 812-9876-5432',
            'city'           => 'Bandung',
            'address'        => 'Jl. Cisitu Indah No. 8',
            'nib'            => '1234567890123',
        ];

        foreach (['verification_status', 'status_verifikasi', 'status'] as $statusCol) {
            if (Schema::hasColumn('umkm_profiles', $statusCol)) {
                $profileData[$statusCol] = $pendingValue;
            }
        }

        if (Schema::hasColumn('umkm_profiles', 'updated_at')) {
            $profileData['updated_at'] = now();
        }

        if (DB::table('umkm_profiles')->where('user_id', $owner->id)->exists()) {
            DB::table('umkm_profiles')->where('user_id', $owner->id)->update($profileData);
        } else {
            if (Schema::hasColumn('umkm_profiles', 'created_at')) {
                $profileData['created_at'] = now();
            }
            DB::table('umkm_profiles')->insert($profileData);
        }

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/admin/verifikasi')
                ->assertPathIs('/admin/verifikasi')
                ->assertSee('Laundry Bersih Kilat')
                ->assertSee('Menunggu')
                ->assertSee('Setujui')
                ->assertSee('Tolak');
        });
    }
}