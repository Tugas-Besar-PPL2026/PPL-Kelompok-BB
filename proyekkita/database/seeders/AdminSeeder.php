<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Membuat akun admin. Admin tidak bisa mendaftar lewat halaman registrasi, jadi akunnya dibuat lewat seeder ini.
 * Email dan kata sandi bisa diatur di .env (SEED_ADMIN_EMAIL, SEED_ADMIN_PASSWORD).
 */
class AdminSeeder extends Seeder
{
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
    }
}
