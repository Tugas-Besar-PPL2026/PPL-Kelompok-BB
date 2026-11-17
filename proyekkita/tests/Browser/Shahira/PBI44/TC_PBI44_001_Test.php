<?php

namespace Tests\Browser\PBI44;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

/**
 * TC-PBI44-001 (sheet PBI-44, Test_Case_ProyekKita_Revisi_PBI16-18_PBI43-45.xlsx).
 *
 * E2E Laravel Dusk: registrasi, login, dan logout dilakukan lewat UI Chrome. Tanpa loginAs/actingAs,
 * tanpa manipulasi session/cookie untuk melewati login, tanpa factory/seeder/SQL tulis.
 * Akun Kandidat/UMKM dibuat lewat halaman registrasi dengan email unik per run. Akun Admin memakai akun
 * yang sudah disiapkan satu kali di database test (DUSK_ADMIN_EMAIL / DUSK_ADMIN_PASSWORD).
 * Status login diverifikasi lewat perilaku aplikasi: /login khusus tamu (pengguna login dialihkan ke halaman
 * awalnya), halaman terproteksi mengalihkan tamu ke /login. Database hanya dibaca untuk verifikasi tambahan.
 */
#[Group('e2e-auth')]
#[Group('pbi44')]
#[Group('TC-PBI44-001')]
class TC_PBI44_001_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use UsesDuskTestDatabase;

    /**
     * TC-PBI44-001 - Login berhasil dengan kredensial valid untuk setiap peran (Kandidat, UMKM, Admin).
     */
    public function test_TC_PBI44_001_login_berhasil_setiap_peran(): void
    {
        $this->browse(function (Browser $browser) {
            // Precondition: akun Kandidat dan UMKM dibuat lewat halaman registrasi lalu logout.
            // Akun Admin sudah disiapkan satu kali di database test (Admin tidak dapat mendaftar lewat aplikasi).
            $kode = $this->kodeUnik();
            $kandidat = "rina.auth.{$kode}@proyekkita.test";
            $umkm = "budi.auth.{$kode}@proyekkita.test";
            $this->daftar($browser, 'kandidat', 'Rina Kandidat', $kandidat, 'Rahasia123');
            $this->keluar($browser);
            $this->daftar($browser, 'umkm', 'Budi UMKM', $umkm, 'Rahasia123');
            $this->keluar($browser);

            // Halaman tujuan, judul, menu milik peran, dan menu yang TIDAK boleh tampil (milik peran lain).
            $peran = [
                'Kandidat' => [$kandidat, 'Rahasia123', '/kandidat/profil', 'Profil & Portofolio', ['Eksplor Proyek', 'Lamaran Saya'], ['Seleksi Kandidat', 'Moderasi Proyek']],
                'UMKM' => [$umkm, 'Rahasia123', '/umkm/profil', 'Profil Usaha', ['Proyek Saya', 'Seleksi Kandidat'], ['Lamaran Saya', 'Moderasi Proyek']],
                'Admin' => [$this->emailAdmin(), $this->sandiAdmin(), '/admin/data-master', 'Data Master', ['Verifikasi UMKM', 'Moderasi Proyek'], ['Lamaran Saya', 'Seleksi Kandidat']],
            ];

            foreach ($peran as $nama => [$email, $sandi, $tujuan, $judul, $menuAda, $menuTidakAda]) {
                // Langkah 1-3: buka /login, isi Email dan Kata sandi, klik 'Masuk'.
                $this->isiFormLogin($browser, $email, $sandi);

                // Langkah 4: halaman tujuan dan menu samping sesuai peran.
                $browser->waitForLocation($tujuan)
                    ->assertPathIs($tujuan)
                    ->assertSeeIn('h1', $judul)
                    ->assertSeeIn('aside.side', 'Keluar');
                foreach ($menuAda as $menu) {
                    $browser->assertSeeIn('aside.side', $menu);
                }
                foreach ($menuTidakAda as $menu) {
                    $browser->assertDontSeeIn('aside.side', $menu);
                }

                // Status login: /login khusus tamu, pengguna login dialihkan ke halaman awalnya.
                $browser->visit('/login')->waitForLocation($tujuan)->assertPathIs($tujuan);

                // Langkah 5: klik 'Keluar' (kecuali Admin, langkah terakhir pada Excel).
                if ($nama !== 'Admin') {
                    $browser->press('Keluar')->waitForLocation('/');
                }
            }
        });
    }

    /** Langkah login lewat form /login sesuai Steps Excel: buka /login, isi Email dan Kata sandi, klik 'Masuk'. */
    private function isiFormLogin(Browser $browser, string $email, string $sandi): Browser
    {
        $browser->visit('/login')->waitFor('@masuk');
        $email === '' ? $browser->clear('email') : $browser->type('email', $email);
        $sandi === '' ? $browser->clear('password') : $browser->type('password', $sandi);

        return $browser->press('@masuk');
    }
}
