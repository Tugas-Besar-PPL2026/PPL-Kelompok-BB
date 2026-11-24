<?php

namespace Tests\Browser\PBI02;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TC_PBI2_001_Test extends DuskTestCase
{
    public function test_tab_data_diri_dengan_profil_tersimpan(): void
    {
        $userData = [
            'name'     => 'Fajar Pratama',
            'password' => Hash::make('password123'),
        ];
        if (Schema::hasColumn('users', 'role')) {
            $userData['role'] = 'kandidat';
        }

        $kandidat = User::firstOrCreate(
            ['email' => 'kandidat.tc201@proyekkita.test'],
            $userData
        );

        if (Schema::hasColumn('users', 'name') && $kandidat->name !== 'Fajar Pratama') {
            $kandidat->update(['name' => 'Fajar Pratama']);
        }
        if (Schema::hasColumn('users', 'role') && $kandidat->role !== 'kandidat') {
            $kandidat->update(['role' => 'kandidat']);
        }

        if (Schema::hasTable('candidate_profiles')) {
            $profileData = ['user_id' => $kandidat->id];
            $candidates = [
                'full_name'  => 'Fajar Pratama',
                'name'       => 'Fajar Pratama',
                'headline'   => 'Desainer UI/UX',
                'university' => 'Telkom University',
                'major'      => 'S1 Desain Komunikasi Visual',
                'summary'    => 'Profil kandidat tersimpan untuk pengujian.',
                'city'       => 'Bandung',
                'domicile'   => 'Bandung',
                'domisili'   => 'Bandung',
                'phone'      => '081234567890',
                'bio'        => 'Profil kandidat tersimpan untuk pengujian.',
            ];

            foreach ($candidates as $col => $val) {
                if (Schema::hasColumn('candidate_profiles', $col)) {
                    $profileData[$col] = $val;
                }
            }

            if (Schema::hasColumn('candidate_profiles', 'updated_at')) {
                $profileData['updated_at'] = now();
            }

            if (DB::table('candidate_profiles')->where('user_id', $kandidat->id)->exists()) {
                DB::table('candidate_profiles')->where('user_id', $kandidat->id)->update($profileData);
            } else {
                if (Schema::hasColumn('candidate_profiles', 'created_at')) {
                    $profileData['created_at'] = now();
                }
                DB::table('candidate_profiles')->insert($profileData);
            }
        }

        $this->browse(function (Browser $browser) use ($kandidat) {
            $browser->loginAs($kandidat)
                ->visit('/kandidat/profil/data-diri')
                ->assertPathIs('/kandidat/profil/data-diri')
                ->assertSee('Fajar Pratama')
                ->assertSee('Kelengkapan Profil')
                ->assertSee('Siapa yang bisa melihat?');
        });
    }
}