<?php

namespace Tests\Browser;

use App\Enums\UserRole;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CandidatePortfolioBrowserTest extends DuskTestCase
{
    public function test_kandidat_dapat_menambah_mengubah_dan_menghapus_portofolio(): void
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

        $profile->portfolios()->where('title', 'like', 'Desain Kemasan Kopi%')->delete();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/kandidat/profil/portofolio')
                ->waitForText('Profil & Portofolio')
                ->assertSee('Tambah Portofolio')
                ->type('#pf_title', 'Desain Kemasan Kopi Arabika')
                ->type('#pf_link', 'https://behance.net/fajarpratama/kopi-arabika')
                ->type('#pf_description', 'Desain kemasan pouch kopi 250g.')
                ->click('#btn-submit-pf')
                ->waitForText('Portofolio berhasil ditambahkan.')
                ->assertSee('Desain Kemasan Kopi Arabika')
                ->assertSee('behance.net/fajarpratama/kopi-arabika')
                ->click('a[aria-label="Ubah Desain Kemasan Kopi Arabika"]')
                ->waitForTextIn('#pf-form-title', 'Ubah Portofolio')
                ->type('#pf_title', 'Desain Kemasan Kopi Spesialti')
                ->click('#btn-submit-pf')
                ->waitForText('Portofolio berhasil diperbarui.')
                ->assertSee('Desain Kemasan Kopi Spesialti')
                ->click('button[aria-label="Hapus Desain Kemasan Kopi Spesialti"]')
                ->waitForText('Portofolio berhasil dihapus.')
                ->assertSee('Portofolio berhasil dihapus.');
        });
    }
}
