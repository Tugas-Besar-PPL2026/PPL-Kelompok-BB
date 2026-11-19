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
 * TC-PBI43-028 (sheet PBI-43, Test_Case_Kelompok_BB_SI4801_1.xlsx).
 *
 * E2E Laravel Dusk: registrasi, login, dan logout dilakukan lewat UI Chrome. Tanpa loginAs/actingAs,
 * tanpa manipulasi session/cookie untuk melewati login, tanpa factory/seeder/SQL tulis.
 * Akun Kandidat/UMKM dibuat lewat halaman registrasi dengan email unik per run.
 * Status login diverifikasi lewat perilaku aplikasi: /login khusus tamu (pengguna login dialihkan ke halaman
 * awalnya), halaman terproteksi mengalihkan tamu ke /login. Database hanya dibaca untuk verifikasi tambahan.
 */
#[Group('e2e-auth')]
#[Group('pbi43')]
#[Group('TC-PBI43-028')]
class TC_PBI43_028_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use UsesDuskTestDatabase;

    /**
     * TC-PBI43-028 - Simpan Data Diri ditolak saat Nama lengkap hasil registrasi dikosongkan.
     */
    public function test_TC_PBI43_028_nama_lengkap_hasil_isian_otomatis_dikosongkan(): void
    {
        $this->browse(function (Browser $browser) {
            $kode = $this->kodeUnik();
            $email = "fajar.kosong.{$kode}@proyekkita.test";

            // Langkah 1-2: daftar sebagai Kandidat, buka Data Diri.
            $this->daftar($browser, 'kandidat', 'Fajar Validasi', $email, 'Rahasia123');
            $this->klikTautan($browser, 'Data Diri')->assertInputValue('@nama-lengkap', 'Fajar Validasi');

            // Langkah 3-5: kosongkan Nama lengkap, isi kolom lain, simpan; ditolak.
            $this->isiDataDiriLain($browser)
                ->clear('@nama-lengkap')
                ->press('@simpan-profil')
                ->waitFor('@galat-nama-lengkap')
                ->assertSeeIn('@galat-nama-lengkap', 'Nama lengkap wajib diisi.')
                ->assertDontSee('Data diri berhasil disimpan.');

            // Verifikasi database (read-only): profil tidak dibuat, nama akun tidak berubah.
            $this->assertSame(0, $this->jumlahProfil('candidate_profiles', $email));
            $this->assertSame('Fajar Validasi', DB::table('users')->where('email', $email)->value('name'));
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
