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
 * TC-PBI43-025 (sheet PBI-43, Test_Case_Kelompok_BB_SI4801_1.xlsx).
 *
 * E2E Laravel Dusk: registrasi, login, dan logout dilakukan lewat UI Chrome. Tanpa loginAs/actingAs,
 * tanpa manipulasi session/cookie untuk melewati login, tanpa factory/seeder/SQL tulis.
 * Akun Kandidat/UMKM dibuat lewat halaman registrasi dengan email unik per run.
 * Status login diverifikasi lewat perilaku aplikasi: /login khusus tamu (pengguna login dialihkan ke halaman
 * awalnya), halaman terproteksi mengalihkan tamu ke /login. Database hanya dibaca untuk verifikasi tambahan.
 */
#[Group('e2e-auth')]
#[Group('pbi43')]
#[Group('TC-PBI43-025')]
class TC_PBI43_025_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use UsesDuskTestDatabase;

    /**
     * TC-PBI43-025 - Nama UMKM hasil registrasi tersimpan ke Profil Usaha tanpa diketik ulang.
     */
    public function test_TC_PBI43_025_simpan_profil_usaha_tanpa_ketik_ulang_nama(): void
    {
        $this->browse(function (Browser $browser) {
            $kode = $this->kodeUnik();
            $nama = 'Kopi Senja Lestari';
            $email = "kopi.prefill.{$kode}@proyekkita.test";

            // Langkah 1-2: daftar sebagai UMKM (Nama usaha terisi, profil belum ada).
            $this->daftar($browser, 'umkm', $nama, $email, 'Rahasia123');
            $browser->waitFor('@nama-usaha')->assertInputValue('@nama-usaha', $nama);
            $this->assertSame(0, $this->jumlahProfil('umkm_profiles', $email));

            // Langkah 3-4: isi kolom lain tanpa mengubah Nama usaha, simpan.
            $this->isiProfilUsahaLain($browser)
                ->press('@simpan-profil')
                ->waitForText('Profil usaha berhasil disimpan.');

            // Langkah 5: Nama usaha tetap, status Menunggu Pemeriksaan, tersimpan di profil.
            $browser->assertInputValue('@nama-usaha', $nama)
                ->assertSeeIn('@status-verifikasi', 'Menunggu Pemeriksaan');
            $this->assertSame(1, $this->jumlahProfil('umkm_profiles', $email));
            $this->assertSame($nama, $this->kolomProfil('umkm_profiles', $email, 'business_name'));
            $this->assertSame('pending', $this->kolomProfil('umkm_profiles', $email, 'verification_status'));
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
