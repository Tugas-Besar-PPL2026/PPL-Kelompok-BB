<?php

namespace Tests\Browser;

use App\Enums\UserRole;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CandidateExperienceBrowserTest extends DuskTestCase
{
    public function test_kandidat_dapat_menambah_mengubah_dan_menghapus_pengalaman_kerja(): void
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

        $profile->experiences()->delete();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/kandidat/profil')
                ->waitForText('Profil & Portofolio')
                ->assertSee('Pengalaman Kerja')
                ->type('#work_type', 'Desainer grafis Himpunan Mahasiswa DKV')
                ->type('#duration', '6 bulan')
                ->type('#exp_description', 'Membuat materi publikasi acara dan konten Instagram himpunan.')
                ->click('#btn-submit-exp')
                ->waitForText('Pengalaman kerja berhasil ditambahkan.')
                ->assertSeeIn('#experience-list', 'Desainer grafis Himpunan Mahasiswa DKV')
                ->click('a[aria-label="Ubah Desainer grafis Himpunan Mahasiswa DKV"]')
                ->waitForTextIn('#exp-form-title', 'Ubah pengalaman')
                ->type('#work_type', 'Desain kemasan produk UMKM (freelance)')
                ->type('#duration', '8 bulan')
                ->click('#btn-submit-exp')
                ->waitForText('Pengalaman kerja berhasil diperbarui.')
                ->assertSeeIn('#experience-list', 'Desain kemasan produk UMKM (freelance)')
                ->click('button[aria-label="Hapus Desain kemasan produk UMKM (freelance)"]')
                ->waitForText('Pengalaman kerja berhasil dihapus.')
                ->assertSee('Pengalaman kerja berhasil dihapus.');
        });
    }
}
