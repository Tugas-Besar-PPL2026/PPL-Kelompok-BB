<?php

namespace Tests\Browser\PBI43;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

/**
 * TC-PBI43-003 (sheet PBI-43, Test_Case_Kelompok_BB_SI4801_1.xlsx).
 *
 * E2E Laravel Dusk: registrasi, login, dan logout dilakukan lewat UI Chrome. Tanpa loginAs/actingAs,
 * tanpa manipulasi session/cookie untuk melewati login, tanpa factory/seeder/SQL tulis.
 * Akun Kandidat/UMKM dibuat lewat halaman registrasi dengan email unik per run.
 * Status login diverifikasi lewat perilaku aplikasi: /login khusus tamu (pengguna login dialihkan ke halaman
 * awalnya), halaman terproteksi mengalihkan tamu ke /login. Database hanya dibaca untuk verifikasi tambahan.
 */
#[Group('e2e-auth')]
#[Group('pbi43')]
#[Group('TC-PBI43-003')]
class TC_PBI43_003_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
      use MenjagaDatabaseE2E;
//use UsesDuskTestDatabase;
      

    /**
     * TC-PBI43-003 - Registrasi berhasil dengan kata sandi sepanjang batas minimum (8 karakter).
     */
    public function test_TC_PBI43_003_kata_sandi_tepat_8_karakter_diterima(): void
    {
        $this->browse(function (Browser $browser) {
            $kode = $this->kodeUnik();
            $email = "rina.sandi8.{$kode}@proyekkita.test";
            $sandi = 'Rahasia1';
            $this->assertSame(8, mb_strlen($sandi));

            // Langkah 1-4: registrasi dengan kata sandi 8 karakter berhasil.
            $this->daftar($browser, 'kandidat', 'Rina Batas Sandi', $email, $sandi);
            $browser->assertPathIs('/kandidat/profil');

            // Akun dapat dipakai login dengan kata sandi tersebut.
            $this->keluar($browser);
            $this->masuk($browser, $email, $sandi, '/kandidat/profil');
            $this->assertSame(1, DB::table('users')->where('email', $email)->count());
        });
    }
}
