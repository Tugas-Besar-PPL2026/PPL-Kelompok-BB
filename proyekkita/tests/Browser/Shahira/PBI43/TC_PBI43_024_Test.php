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
 * TC-PBI43-024 (sheet PBI-43, Test_Case_Kelompok_BB_SI4801_1.xlsx).
 *
 * E2E Laravel Dusk: registrasi, login, dan logout dilakukan lewat UI Chrome. Tanpa loginAs/actingAs,
 * tanpa manipulasi session/cookie untuk melewati login, tanpa factory/seeder/SQL tulis.
 * Akun Kandidat/UMKM dibuat lewat halaman registrasi dengan email unik per run.
 * Status login diverifikasi lewat perilaku aplikasi: /login khusus tamu (pengguna login dialihkan ke halaman
 * awalnya), halaman terproteksi mengalihkan tamu ke /login. Database hanya dibaca untuk verifikasi tambahan.
 */
#[Group('e2e-auth')]
#[Group('pbi43')]
#[Group('TC-PBI43-024')]
class TC_PBI43_024_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use UsesDuskTestDatabase;

    /**
     * TC-PBI43-024 - Nama lengkap hasil registrasi tersimpan ke profil Kandidat tanpa diketik ulang.
     */
    public function test_TC_PBI43_024_simpan_data_diri_tanpa_ketik_ulang_nama(): void
    {
        $this->browse(function (Browser $browser) {
            $kode = $this->kodeUnik();
            $nama = 'Fajar Pratama';
            $email = "fajar.prefill.{$kode}@proyekkita.test";

            // Langkah 1-2: daftar sebagai Kandidat, buka Data Diri (Nama lengkap terisi, profil belum ada).
            $this->daftar($browser, 'kandidat', $nama, $email, 'Rahasia123');
            $this->klikTautan($browser, 'Data Diri')->assertInputValue('@nama-lengkap', $nama);
            $this->assertSame(0, $this->jumlahProfil('candidate_profiles', $email));

            // Langkah 3-4: isi kolom lain tanpa mengubah Nama lengkap, simpan.
            $this->isiDataDiriLain($browser)
                ->press('@simpan-profil')
                ->waitForText('Data diri berhasil disimpan.');

            // Langkah 5: Nama lengkap tetap dan tersimpan di profil.
            $browser->assertInputValue('@nama-lengkap', $nama);
            $this->assertSame(1, $this->jumlahProfil('candidate_profiles', $email));
            $this->assertSame($nama, $this->kolomProfil('candidate_profiles', $email, 'full_name'));
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
