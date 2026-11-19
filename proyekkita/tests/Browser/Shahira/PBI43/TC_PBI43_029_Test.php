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
 * TC-PBI43-029 (sheet PBI-43, Test_Case_Kelompok_BB_SI4801_1.xlsx).
 *
 * E2E Laravel Dusk: registrasi, login, dan logout dilakukan lewat UI Chrome. Tanpa loginAs/actingAs,
 * tanpa manipulasi session/cookie untuk melewati login, tanpa factory/seeder/SQL tulis.
 * Akun Kandidat/UMKM dibuat lewat halaman registrasi dengan email unik per run.
 * Status login diverifikasi lewat perilaku aplikasi: /login khusus tamu (pengguna login dialihkan ke halaman
 * awalnya), halaman terproteksi mengalihkan tamu ke /login. Database hanya dibaca untuk verifikasi tambahan.
 */
#[Group('e2e-auth')]
#[Group('pbi43')]
#[Group('TC-PBI43-029')]
class TC_PBI43_029_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use UsesDuskTestDatabase;

    /**
     * TC-PBI43-029 - Simpan Profil Usaha ditolak saat Nama usaha hasil registrasi dikosongkan.
     */
    public function test_TC_PBI43_029_nama_usaha_hasil_isian_otomatis_dikosongkan(): void
    {
        $this->browse(function (Browser $browser) {
            $kode = $this->kodeUnik();
            $email = "kopi.kosong.{$kode}@proyekkita.test";

            // Langkah 1-2: daftar sebagai UMKM, kosongkan Nama usaha, isi kolom lain.
            $this->daftar($browser, 'umkm', 'Kopi Validasi', $email, 'Rahasia123');
            $browser->waitFor('@nama-usaha')->assertInputValue('@nama-usaha', 'Kopi Validasi');

            // Langkah 3-4: simpan; ditolak dan status tetap 'Belum diisi'.
            $this->isiProfilUsahaLain($browser)
                ->clear('@nama-usaha')
                ->press('@simpan-profil')
                ->waitFor('@galat-nama-usaha')
                ->assertSeeIn('@galat-nama-usaha', 'Nama usaha wajib diisi.')
                ->assertSeeIn('@status-verifikasi', 'Belum diisi')
                ->assertDontSee('Profil usaha berhasil disimpan.');

            // Verifikasi database (read-only): profil tidak dibuat, nama akun tidak berubah.
            $this->assertSame(0, $this->jumlahProfil('umkm_profiles', $email));
            $this->assertSame('Kopi Validasi', DB::table('users')->where('email', $email)->value('name'));
        });
    }

    /** Mengisi kolom Profil Usaha selain Nama usaha dengan nilai valid. */
    private function isiProfilUsahaLain(Browser $browser): Browser
    {
        return $browser->type('@bidang-usaha', 'Jasa')
            ->type('@deskripsi-usaha', 'Kedai kopi lokal untuk pengujian otomatis.')
            ->type('@kontak-usaha', '081234567890')
            ->type('@kota-usaha', 'Bandung')
            ->type('@alamat-usaha', 'Jl. Pengujian No. 2, Bandung')
            ->type('@nib', '1234567890123');
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
