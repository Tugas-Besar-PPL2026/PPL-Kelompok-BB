<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Skill;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Mengisi data awal kategori proyek dan keterampilan. Data ini nantinya bisa ditambah, diubah, atau dinonaktifkan oleh admin.
 */
class MasterDataSeeder extends Seeder
{
    public const CATEGORIES = [
        'Teknologi & Web',
        'Desain Grafis & Branding',
        'Pemasaran & Media Sosial',
        'Riset Pasar & Konten',
        'Keuangan & Administrasi',
    ];

    public const SKILLS = [
        'Figma', 'Desain Grafis', 'Desain Kemasan', 'UI/UX Design', 'Desain Logo', 'Ilustrasi', 'Canva',
        'Laravel', 'MySQL', 'Copywriting', 'Microsoft Excel', 'Fotografi Produk', 'Social Media',
    ];

    /**
     * Master data khusus automated test E2E (dipakai form Buat Proyek dan profil kandidat di test Dusk).
     * Harus sama dengan KATEGORI_E2E dan KETERAMPILAN_E2E di tests/Browser/Concerns/AlurE2EProyekKita.php.
     */
    public const E2E_CATEGORY = 'Dusk Desain Kreatif';

    public const E2E_SKILL = 'Dusk Desain Grafis';

    public function run(): void
    {
        foreach (self::CATEGORIES as $name) {
            Category::firstOrCreate(['name' => $name], ['is_active' => true]);
        }

        foreach (self::SKILLS as $name) {
            Skill::firstOrCreate(['name' => $name], ['is_active' => true]);
        }

        if (self::databaseDusk()) {
            $this->seedMasterDataE2E();
        }
    }

    /**
     * Kategori dan keterampilan E2E. Hanya dijalankan di database Dusk; database development/production
     * tidak mendapat data ini. Idempotent: tidak membuat duplikat dan mengaktifkan kembali bila dinonaktifkan.
     */
    public function seedMasterDataE2E(): void
    {
        Category::updateOrCreate(['name' => self::E2E_CATEGORY], ['is_active' => true]);
        Skill::updateOrCreate(['name' => self::E2E_SKILL], ['is_active' => true]);
    }

    /** Database khusus automated testing: bukan production dan nama database mengandung "dusk". */
    public static function databaseDusk(): bool
    {
        return ! app()->environment('production')
            && str_contains(strtolower(basename((string) DB::connection()->getDatabaseName())), 'dusk');
    }
}
