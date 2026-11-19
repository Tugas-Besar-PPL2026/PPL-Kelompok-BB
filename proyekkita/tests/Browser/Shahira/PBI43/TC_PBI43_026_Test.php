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
 * TC-PBI43-026 (sheet PBI-43, Test_Case_Kelompok_BB_SI4801_1.xlsx).
 *
 * E2E Laravel Dusk: registrasi, login, dan logout dilakukan lewat UI Chrome. Tanpa loginAs/actingAs,
 * tanpa manipulasi session/cookie untuk melewati login, tanpa factory/seeder/SQL tulis.
 * Akun Kandidat/UMKM dibuat lewat halaman registrasi dengan email unik per run.
 * Status login diverifikasi lewat perilaku aplikasi: /login khusus tamu (pengguna login dialihkan ke halaman
 * awalnya), halaman terproteksi mengalihkan tamu ke /login. Database hanya dibaca untuk verifikasi tambahan.
 */
#[Group('e2e-auth')]
#[Group('pbi43')]
#[Group('TC-PBI43-026')]
class TC_PBI43_026_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use UsesDuskTestDatabase;

    /**
     * TC-PBI43-026 - Nama lengkap dapat diubah sebelum disimpan dan nama di profil diutamakan setelah login ulang.
     */
    public function test_TC_PBI43_026_ubah_nama_lengkap_hasil_isian_otomatis(): void
    {
        $this->browse(function (Browser $browser) {
            $kode = $this->kodeUnik();
            $email = "fajar.ubah.{$kode}@proyekkita.test";
            $sandi = 'Rahasia123';

            // Langkah 1-2: daftar, buka Data Diri, ganti Nama lengkap, isi kolom lain, simpan.
            $this->daftar($browser, 'kandidat', 'Fajar Registrasi', $email, $sandi);
            $this->klikTautan($browser, 'Data Diri')->assertInputValue('@nama-lengkap', 'Fajar Registrasi');
            $this->isiDataDiriLain($browser)
                ->type('@nama-lengkap', 'Fajar Pratama Putra')
                ->press('@simpan-profil')
                ->waitForText('Data diri berhasil disimpan.');

            // Langkah 3-5: keluar, login kembali, buka Data Diri; nama di profil diutamakan.
            $this->keluar($browser);
            $this->masuk($browser, $email, $sandi, '/kandidat/profil');
            $this->klikTautan($browser, 'Data Diri')->assertInputValue('@nama-lengkap', 'Fajar Pratama Putra');

            // Verifikasi database (read-only): profil berisi nama baru, nama akun tidak berubah.
            $this->assertSame('Fajar Pratama Putra', $this->kolomProfil('candidate_profiles', $email, 'full_name'));
            $this->assertSame('Fajar Registrasi', DB::table('users')->where('email', $email)->value('name'));
        });
    }

    /** Mengisi kolom Data Diri selain Nama lengkap dengan nilai valid. */
    private function isiDataDiriLain(Browser $browser): Browser
    {
        return $browser->type('@domisili', 'Bandung')
            ->type('@headline', 'Mahasiswa Desain Komunikasi Visual')
            ->type('@universitas', 'Universitas Pengujian')
            ->type('@program-studi', 'Desain Komunikasi Visual')
            ->type('@ringkasan', 'Berpengalaman membuat desain kemasan dan media promosi untuk UMKM.');
    }

    /** Jumlah baris profil ($tabel) milik akun dengan email ini (read-only). */
    private function jumlahProfil(string $tabel, string $email): int
    {
        return DB::table($tabel)
            ->join('users', 'users.id', '=', "{$tabel}.user_id")
            ->where('users.email', $email)
            ->count();
    }

    /** Nilai satu kolom profil milik akun dengan email ini (read-only). */
    private function kolomProfil(string $tabel, string $email, string $kolom): ?string
    {
        return DB::table($tabel)
            ->join('users', 'users.id', '=', "{$tabel}.user_id")
            ->where('users.email', $email)
            ->value("{$tabel}.{$kolom}");
    }
}
