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
 * TC-PBI43-004 (sheet PBI-43, Test_Case_Kelompok_BB_SI4801_1.xlsx).
 *
 * E2E Laravel Dusk: registrasi, login, dan logout dilakukan lewat UI Chrome. Tanpa loginAs/actingAs,
 * tanpa manipulasi session/cookie untuk melewati login, tanpa factory/seeder/SQL tulis.
 * Akun Kandidat/UMKM dibuat lewat halaman registrasi dengan email unik per run.
 * Status login diverifikasi lewat perilaku aplikasi: /login khusus tamu (pengguna login dialihkan ke halaman
 * awalnya), halaman terproteksi mengalihkan tamu ke /login. Database hanya dibaca untuk verifikasi tambahan.
 */
#[Group('e2e-auth')]
#[Group('pbi43')]
#[Group('TC-PBI43-004')]
class TC_PBI43_004_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use UsesDuskTestDatabase;

    /**
     * TC-PBI43-004 - Nama lengkap sepanjang batas maksimum (100 karakter) diterima dan terisi utuh di Data Diri.
     */
    public function test_TC_PBI43_004_nama_lengkap_100_karakter_diterima(): void
    {
        $this->browse(function (Browser $browser) {
            $kode = $this->kodeUnik();
            $email = "nama.batas.kandidat.{$kode}@proyekkita.test";

            // Langkah 1-2: kolom nama hanya menerima 100 karakter.
            $browser->visit('/daftar/kandidat')
                ->waitFor('@daftar')
                ->type('name', str_repeat('K', 101));
            $this->assertSame(100, mb_strlen((string) $browser->inputValue('name')), 'Kolom nama harus dibatasi 100 karakter.');

            // Langkah 3-4: registrasi dengan nama 101 karakter (yang masuk ke form 100 karakter).
            $this->daftar($browser, 'kandidat', str_repeat('K', 101), $email, 'Rahasia123');

            // Langkah 5: nama di profil terisi utuh 100 karakter.
            $this->klikTautan($browser, 'Data Diri')
                ->assertInputValue('@nama-lengkap', str_repeat('K', 100));

            // Verifikasi database (read-only): nama akun tepat 100 karakter.
            $this->assertSame(str_repeat('K', 100), DB::table('users')->where('email', $email)->value('name'));
        });
    }
}
