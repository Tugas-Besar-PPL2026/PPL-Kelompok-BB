<?php

namespace Tests\Browser;

use App\Enums\UserRole;
use App\Models\Skill;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CandidateSkillBrowserTest extends DuskTestCase
{
    public function test_kandidat_dapat_mencari_menambah_dan_menghapus_keterampilan(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'dusk.kandidat@proyekkita.test'],
            ['name' => 'Fajar Pratama', 'password' => 'password', 'role' => UserRole::Kandidat]
        );

        $profile = $user->candidateProfile()->updateOrCreate([], [
            'full_name' => 'Fajar Pratama',
            'headline' => 'Desainer grafis & kemasan produk UMKM',
            'university' => 'Telkom University',
            'major' => 'S1 Desain Komunikasi Visual',
            'city' => 'Kota Bandung',
            'summary' => 'Mahasiswa DKV yang berfokus pada desain kemasan.',
        ]);

        $figma = Skill::firstOrCreate(['name' => 'Figma'], ['is_active' => true]);
        $uiux = Skill::firstOrCreate(['name' => 'UI/UX Design'], ['is_active' => true]);
        $profile->skills()->sync([$figma->id]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/kandidat/profil')
                ->waitForText('Profil & Portofolio')
                ->assertSee('Keterampilan')
                ->assertSeeIn('#selected-skills', 'Figma')
                ->type('#cari-keterampilan', 'UI/UX')
                ->assertSeeIn('#skill-list', 'UI/UX Design')
                ->press('+ tambah')
                ->waitForText('Keterampilan berhasil ditambahkan.')
                ->assertSeeIn('#selected-skills', 'UI/UX Design')
                ->click('button[aria-label="Hapus UI/UX Design"]')
                ->waitForText('Keterampilan berhasil dihapus.')
                ->assertSee('Keterampilan berhasil dihapus.');
        });
    }
}
