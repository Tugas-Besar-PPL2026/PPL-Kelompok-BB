<?php

namespace Tests\Browser\Concerns;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use RuntimeException;

/**
 * Pengaman database untuk test E2E PBI-16, PBI-17, dan PBI-18.
 *
 * Aturan dosen yang dijaga di sini:
 *  - Database automation testing adalah MySQL TERPISAH dari development (nama database wajib mengandung "dusk").
 *  - Database bersifat persistent: trait ini TIDAK menjalankan migrate:fresh, RefreshDatabase, truncate,
 *    factory, atau seeder. Data lama dibiarkan apa adanya.
 *  - DuskTestCase hanya menjalankan "migrate" biasa (menambah tabel baru bila ada), tidak menghapus data.
 *
 * Dipanggil otomatis oleh Laravel (method setUp + nama trait) sebelum test berjalan,
 * sehingga test berhenti sebelum menyentuh database yang salah.
 */
trait MenjagaDatabaseE2E
{
    public function setUpMenjagaDatabaseE2E(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('Test E2E tidak boleh dijalankan di environment production.');
        }

        $koneksi = (string) config('database.default');
        $driver = (string) config("database.connections.{$koneksi}.driver");
        $database = (string) DB::connection($koneksi)->getDatabaseName();

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            throw new RuntimeException(
                "Test E2E PBI-16/17/18 wajib memakai MySQL khusus automation testing. Driver aktif: {$driver}. "
                .'Periksa DB_CONNECTION di .env.dusk.local dan pastikan MySQL berjalan di port yang benar '
                .'(DuskTestCase beralih ke SQLite bila port 3306 tidak dapat dihubungi).'
            );
        }

        if (! str_contains(strtolower($database), 'dusk')) {
            throw new RuntimeException(
                "Nama database test harus mengandung kata 'dusk' agar database development tidak tersentuh. Aktif: {$database}."
            );
        }

        // Alur E2E melewati beberapa halaman dan redirect; beri waktu tunggu yang cukup.
        Browser::$waitSeconds = (int) env('DUSK_WAIT_SECONDS', 15);
    }
}
