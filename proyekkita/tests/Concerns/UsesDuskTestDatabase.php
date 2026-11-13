<?php

namespace Tests\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use RuntimeException;
use Throwable;

/**
 * Pengaman database untuk Laravel Dusk (PBI-16, PBI-17, PBI-18).
 *
 * Masalah yang dicegah: test Dusk menulis data (user, proyek, lamaran) ke database yang dipakai server aplikasi.
 * Tanpa pengaman, data itu bisa tercampur dengan database development.
 *
 * Yang dilakukan trait ini (otomatis sebelum setiap test):
 *  1. Menolak berjalan bila APP_ENV = production.
 *  2. Memaksa proses test memakai SATU file SQLite khusus: database/dusk.sqlite (bukan database/database.sqlite,
 *     bukan :memory:). File ini sudah ada di .gitignore dan aman dibuang kapan saja.
 *  3. Menjalankan migrate:fresh HANYA pada koneksi tersebut, sehingga setiap test mulai dari database kosong.
 *  4. Sekali per kelas test, memastikan SERVER aplikasi yang diakses browser memakai database yang sama.
 *     Caranya: membuat user penanda ber-ID sangat besar di dusk.sqlite, lalu meminta browser login sebagai user itu.
 *     Jika server memakai database lain (misalnya database development), user itu tidak ditemukan dan test
 *     dihentikan sebelum ada data yang ditulis atau dihapus di database tersebut.
 *
 * Server harus dijalankan dengan DB_DATABASE yang menunjuk ke file yang sama. Lihat
 * dokumentasi-pengujian/PANDUAN_MENJALANKAN_TEST.md.
 *
 * Batasan: pemeriksaan penanda berdasarkan ID user. Peluang database development kebetulan memiliki user dengan
 * ID acak 900.000.000 sampai 999.999.999 dianggap dapat diabaikan.
 */
trait UsesDuskTestDatabase
{
    private static bool $duskServerVerified = false;

    /** Dipanggil otomatis oleh Laravel (setUp + nama trait) setelah aplikasi dibuat. */
    public function setUpUsesDuskTestDatabase(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('Test Dusk tidak boleh dijalankan di environment production.');
        }

        $path = base_path('database'.DIRECTORY_SEPARATOR.'dusk.sqlite');

        if (! file_exists($path)) {
            touch($path);
        }

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => $path,
        ]);
        DB::purge('sqlite');

        $aktif = DB::connection('sqlite')->getDatabaseName();
        if (config('database.default') !== 'sqlite' || $aktif !== $path || basename($aktif) !== 'dusk.sqlite') {
            throw new RuntimeException("Database test bukan database/dusk.sqlite (aktif: {$aktif}). Test dihentikan.");
        }

        // Hanya menyentuh koneksi di atas (dusk.sqlite).
        Artisan::call('migrate:fresh', ['--database' => 'sqlite', '--force' => true]);

        if (! self::$duskServerVerified) {
            $this->pastikanServerMemakaiDatabaseDusk();
            self::$duskServerVerified = true;
        }
    }

    private function pastikanServerMemakaiDatabaseDusk(): void
    {
        $penanda = User::factory()->kandidat()->create(['id' => random_int(900000000, 999999999)]);

        // Trait ini berjalan di tahap setUp awal, sebelum DuskTestCase menetapkan pengaturan Browser.
        // Tetapkan sendiri dengan nilai yang sama agar pemeriksaan ini tidak gagal karena baseUrl kosong.
        Browser::$baseUrl = method_exists($this, 'baseUrl') ? $this->baseUrl() : rtrim((string) config('app.url'), '/');
        Browser::$storeScreenshotsAt = base_path('tests/Browser/screenshots');
        Browser::$storeConsoleLogAt = base_path('tests/Browser/console');

        try {
            $this->browse(function (Browser $browser) use ($penanda) {
                $browser->loginAs($penanda)->assertAuthenticatedAs($penanda);
            });
        } catch (Throwable $e) {
            throw new RuntimeException(
                'Server aplikasi tidak memakai database/dusk.sqlite, atau server tidak dapat dijangkau (APP_URL). '
                .'Test dihentikan agar tidak menyentuh database lain. Periksa PANDUAN_MENJALANKAN_TEST.md. '
                .'Detail: '.$e->getMessage(),
                0,
                $e,
            );
        } finally {
            $penanda->delete();
        }
    }
}
