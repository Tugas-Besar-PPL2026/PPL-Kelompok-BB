<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * php artisan migrate:fresh --seed
     *
     * AdminSeeder (akun admin) dan MasterDataSeeder (kategori & keterampilan) selalu dijalankan.
     * DemoSeeder berisi data contoh untuk mencoba aplikasi dan hanya dijalankan di environment local/testing.
     */
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            MasterDataSeeder::class,
        ]);

        if (app()->environment(['local', 'testing'])) {
            $this->call(DemoSeeder::class);
        }
    }
}
