<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Skill;
use Illuminate\Database\Seeder;

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

    public function run(): void
    {
        foreach (self::CATEGORIES as $name) {
            Category::firstOrCreate(['name' => $name], ['is_active' => true]);
        }

        foreach (self::SKILLS as $name) {
            Skill::firstOrCreate(['name' => $name], ['is_active' => true]);
        }
    }
}
