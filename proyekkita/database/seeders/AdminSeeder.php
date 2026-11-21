<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\VerificationStatus;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Membuat akun admin. Admin tidak bisa mendaftar lewat halaman registrasi, jadi akunnya dibuat lewat seeder ini.
 * Email dan kata sandi bisa diatur di .env (SEED_ADMIN_EMAIL, SEED_ADMIN_PASSWORD).
 *
 * Khusus database Dusk (nama database mengandung "dusk", bukan production), seeder ini juga menyiapkan akun
 * default automated test E2E: UMKM terverifikasi dengan profil lengkap dan Kandidat yang memenuhi syarat melamar.
 * Jalankan sekali di database Dusk (bukan dari dalam test):
 *   php artisan db:seed --class=AdminSeeder --env=dusk.local
 * Di .env.dusk.local, DUSK_ADMIN_EMAIL/DUSK_ADMIN_PASSWORD harus sama dengan akun Admin di atas
 * (default: admin@proyekkita.test / password).
 */
class AdminSeeder extends Seeder
{
    /** Default akun E2E; sama dengan default helper tests/Browser/Concerns (DUSK_UMKM_*, DUSK_KANDIDAT_*). */
    public const E2E_UMKM_EMAIL = 'dusk.umkm.lamaran@proyekkita.test';

    public const E2E_KANDIDAT_EMAIL = 'dusk.kandidat.default@proyekkita.test';

    public const E2E_PASSWORD = 'Password123!';

    public const E2E_NAMA_USAHA = 'Dusk UMKM Lamaran';

    public const E2E_NAMA_KANDIDAT = 'Kandidat Default Dusk';

    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('SEED_ADMIN_EMAIL', 'admin@proyekkita.test')],
            [
                'name' => 'Admin ProyekKita',
                'password' => env('SEED_ADMIN_PASSWORD', 'password'),
                'role' => UserRole::Admin,
            ],
        );

        if (MasterDataSeeder::databaseDusk()) {
            $this->seedAkunDefaultE2E();
        }
    }

    /**
     * Akun default E2E (idempotent: updateOrCreate per email, tidak membuat duplikat).
     * Keterampilan kandidat memakai master data E2E dari MasterDataSeeder.
     */
    private function seedAkunDefaultE2E(): void
    {
        (new MasterDataSeeder)->seedMasterDataE2E();
        $keterampilan = Skill::where('name', MasterDataSeeder::E2E_SKILL)->firstOrFail();

        // UMKM default: profil usaha lengkap dan sudah Terverifikasi, sehingga dapat langsung membuat proyek.
        $umkm = User::updateOrCreate(
            ['email' => mb_strtolower((string) env('DUSK_UMKM_EMAIL', self::E2E_UMKM_EMAIL))],
            [
                'name' => self::E2E_NAMA_USAHA,
                'password' => (string) env('DUSK_UMKM_PASSWORD', self::E2E_PASSWORD),
                'role' => UserRole::Umkm,
            ],
        );
        $umkm->umkmProfile()->updateOrCreate([], [
            'business_name' => self::E2E_NAMA_USAHA,
            'description' => 'Usaha percetakan dan kemasan untuk pengujian otomatis.',
            'business_field' => 'Jasa',
            'contact_phone' => '081234567890',
            'city' => 'Bandung',
            'address' => 'Jl. Pengujian No. 1, Bandung',
            'nib' => '1234567890123',
            'verification_status' => VerificationStatus::Verified,
            'rejection_reason' => null,
            'verified_at' => now(),
        ]);

        // Kandidat default: 6 data diri lengkap + 1 keterampilan, sehingga memenuhi syarat melamar.
        $kandidat = User::updateOrCreate(
            ['email' => mb_strtolower((string) env('DUSK_KANDIDAT_EMAIL', self::E2E_KANDIDAT_EMAIL))],
            [
                'name' => self::E2E_NAMA_KANDIDAT,
                'password' => (string) env('DUSK_KANDIDAT_PASSWORD', self::E2E_PASSWORD),
                'role' => UserRole::Kandidat,
            ],
        );
        $profil = $kandidat->candidateProfile()->updateOrCreate([], [
            'full_name' => self::E2E_NAMA_KANDIDAT,
            'headline' => 'Mahasiswa Desain Komunikasi Visual',
            'university' => 'Universitas Pengujian',
            'major' => 'Desain Komunikasi Visual',
            'city' => 'Bandung',
            'summary' => 'Berpengalaman membuat desain kemasan dan media promosi untuk UMKM.',
        ]);
        $profil->skills()->syncWithoutDetaching([$keterampilan->id]);
    }
}
