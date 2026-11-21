<?php

namespace Tests\Browser\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use RuntimeException;

/**
 * Data default untuk test E2E PBI-16, PBI-17, dan PBI-18 (dipakai bersama AlurE2EProyekKita).
 *
 * Admin, UMKM terverifikasi (profil lengkap), Kandidat default, dan master data E2E disiapkan SATU KALI
 * lewat seeder existing (AdminSeeder + MasterDataSeeder, bagian E2E hanya aktif di database Dusk), bukan dari dalam test:
 *   php artisan db:seed --class=AdminSeeder --env=dusk.local
 *
 * Test tidak lagi memeriksa akun Admin/UMKM lewat browser, memverifikasi UMKM, atau mengisi profil yang bukan bagian uji.
 * Proyek, moderasi, lamaran, dan kandidat dengan kondisi khusus tetap dibuat lewat UI di dalam setiap test.
 * Trait ini hanya MEMBACA database; tidak menjalankan seeder, factory, atau SQL tulis.
 */
trait DataDefaultPbi16E2E
{
    private static bool $dataDefaultPbi16Siap = false;

    private const PERINTAH_SEEDER = 'php artisan db:seed --class=AdminSeeder --env=dusk.local';

    /** Akun Kandidat default yang sudah memenuhi syarat melamar (6 data diri + 1 keterampilan). */
    protected function kandidatDefault(): array
    {
        return [
            'nama' => 'Kandidat Default Dusk',
            'email' => mb_strtolower((string) env('DUSK_KANDIDAT_EMAIL', 'dusk.kandidat.default@proyekkita.test')),
            'sandi' => (string) env('DUSK_KANDIDAT_PASSWORD', self::SANDI_E2E),
        ];
    }

    /** Pemeriksaan data default untuk test PBI-16; perilaku dan pesan sama seperti sebelumnya. */
    protected function pastikanDataDefaultPbi16(): void
    {
        $this->pastikanDataDefaultE2E('PBI-16');
    }

    /**
     * Read-only: memastikan Admin, UMKM terverifikasi, Kandidat default, dan master data E2E sudah di-seed.
     * Hanya membaca database (tanpa browser/login); bila belum lengkap, test dihentikan dengan petunjuk
     * menjalankan seeder (tidak ada data yang dibuat otomatis).
     */
    protected function pastikanDataDefaultE2E(string $label = 'E2E'): void
    {
        if (self::$dataDefaultPbi16Siap) {
            return;
        }

        $kurang = [];

        if (! DB::table('categories')->where('name', self::KATEGORI_E2E)->where('is_active', true)->exists()) {
            $kurang[] = 'kategori aktif "'.self::KATEGORI_E2E.'"';
        }

        $keterampilanId = DB::table('skills')->where('name', self::KETERAMPILAN_E2E)->where('is_active', true)->value('id');
        if ($keterampilanId === null) {
            $kurang[] = 'keterampilan aktif "'.self::KETERAMPILAN_E2E.'"';
        }

        $this->periksaAkunDefault($kurang, 'Admin', $this->emailAdmin(), $this->sandiAdmin(), 'admin');

        $umkmId = $this->periksaAkunDefault($kurang, 'UMKM', $this->emailUmkm(), $this->sandiUmkm(), 'umkm');
        if ($umkmId !== null && ! DB::table('umkm_profiles')->where('user_id', $umkmId)->where('verification_status', 'verified')->exists()) {
            $kurang[] = 'profil usaha UMKM default berstatus Terverifikasi';
        }

        $kandidat = $this->kandidatDefault();
        $kandidatId = $this->periksaAkunDefault($kurang, 'Kandidat', $kandidat['email'], $kandidat['sandi'], 'kandidat');
        if ($kandidatId !== null) {
            $profilId = DB::table('candidate_profiles')->where('user_id', $kandidatId)->value('id');
            $punyaKeterampilan = $profilId !== null && $keterampilanId !== null
                && DB::table('candidate_skill')->where('candidate_profile_id', $profilId)->where('skill_id', $keterampilanId)->exists();
            if (! $punyaKeterampilan) {
                $kurang[] = 'profil Kandidat default lengkap dengan keterampilan "'.self::KETERAMPILAN_E2E.'"';
            }
        }

        if ($kurang !== []) {
            throw new RuntimeException(
                "Data default {$label} belum siap di database Dusk: ".implode('; ', $kurang).'. '
                .'Jalankan sekali: '.self::PERINTAH_SEEDER
            );
        }

        self::$dataDefaultPbi16Siap = true;
    }

    /**
     * Kandidat tambahan lewat UI untuk test yang butuh kandidat berbeda atau kondisi profil tertentu.
     * Sama dengan siapkanKandidat() tanpa langkah Admin memeriksa Data Master (master data sudah di-seed).
     * $tingkat: 'lengkap' | 'tanpa-keterampilan' | 'tanpa-profil'. Diakhiri tanpa sesi login.
     */
    protected function siapkanKandidatTambahan(Browser $browser, string $label, string $tingkat = 'lengkap'): array
    {
        $this->pastikanDataDefaultE2E();

        $akun = $this->daftarKandidatBaru($browser, $label);

        if ($tingkat !== 'tanpa-profil') {
            $this->isiDataDiri($browser, $akun['nama']);
        }

        if ($tingkat === 'lengkap') {
            $this->tambahKeterampilan($browser);
        }

        $this->keluar($browser);

        return $akun;
    }

    /** Read-only: akun ada, perannya benar, dan kata sandinya cocok. Mengembalikan id user bila ada. */
    private function periksaAkunDefault(array &$kurang, string $label, string $email, string $sandi, string $peran): ?int
    {
        $akun = DB::table('users')->where('email', mb_strtolower($email))->first();

        if ($akun === null) {
            $kurang[] = "akun {$label} {$email}";

            return null;
        }

        if ($akun->role !== $peran || ! Hash::check($sandi, $akun->password)) {
            $kurang[] = "akun {$label} {$email} dengan peran {$peran} dan kata sandi sesuai .env.dusk.local";
        }

        return (int) $akun->id;
    }
}
