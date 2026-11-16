<?php

namespace Tests\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use RuntimeException;
use Throwable;

/**
 * Pengaman database untuk Laravel Dusk.
 *
 * Masalah yang dicegah: test Dusk menulis dan menghapus data di database yang dipakai untuk development.
 *
 * Yang dilakukan trait ini (otomatis sebelum setiap test):
 *  1. Menolak berjalan bila APP_ENV = production.
 *  2. Memakai koneksi database dari .env.dusk.local:
 *     - MySQL: nama database WAJIB mengandung kata "dusk" (contoh: proyekkita_dusk).
 *     - SQLite: selalu dipaksa memakai file database/dusk.sqlite.
 *     Selain itu test dihentikan, sehingga database development tidak pernah tersentuh.
 *  3. Menjalankan migrate:fresh HANYA pada database Dusk tersebut, sehingga setiap test mulai dari database kosong.
 *  4. Sekali per kelas test, memastikan SERVER aplikasi yang diakses browser memakai database yang sama.
 *     Caranya: membuat user penanda ber-ID sangat besar, lalu meminta browser login sebagai user itu.
 *     Jika server memakai database lain, user itu tidak ditemukan dan test dihentikan.
 *
 * Batasan: pemeriksaan penanda berdasarkan ID user. Peluang database lain kebetulan memiliki user dengan
 * ID acak 900.000.000 sampai 999.999.999 dianggap dapat diabaikan.
 */
trait UsesDuskTestDatabase
{
        /**
     * Browser dengan jeda setelah setiap aksi (buka halaman, ketik, klik, pilih, centang).
     * Lama jeda diatur lewat DUSK_JEDA (milidetik) di .env.dusk.local. Tanpa DUSK_JEDA, kecepatan normal.
     */
    protected function newBrowser($driver)
    {
        return new class($driver) extends Browser
        {
            public function visit($url) { return $this->jeda(parent::visit($url)); }
            public function type($field, $value) { return $this->jeda(parent::type($field, $value)); }
            public function select($field, $value = null) { return $this->jeda(parent::select($field, $value)); }
            public function radio($field, $value) { return $this->jeda(parent::radio($field, $value)); }
            public function check($field, $value = null) { return $this->jeda(parent::check($field, $value)); }
            public function uncheck($field, $value = null) { return $this->jeda(parent::uncheck($field, $value)); }
            public function press($button) { return $this->jeda(parent::press($button)); }
            public function click($selector = null) { return $this->jeda(parent::click($selector)); }
            public function clickAtXPath($expression) { return $this->jeda(parent::clickAtXPath($expression)); }

            private function jeda($hasil)
            {
                $ms = (int) env('DUSK_JEDA', 0);

                if ($ms > 0) {
                    usleep($ms * 1000);
                }

                return $hasil;
            }
        };
    }
    private static bool $duskServerVerified = false;

    /** Dipanggil otomatis oleh Laravel (setUp + nama trait) setelah aplikasi dibuat. */
    public function setUpUsesDuskTestDatabase(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('Test Dusk tidak boleh dijalankan di environment production.');
        }
        $this->bersihkanSesiBrowser();

        $koneksi = config('database.default');

        if ($koneksi === 'sqlite') {
            $path = base_path('database'.DIRECTORY_SEPARATOR.'dusk.sqlite');

            if (! file_exists($path)) {
                touch($path);
            }

            config(['database.connections.sqlite.database' => $path]);
            DB::purge('sqlite');
        }

        $aktif = (string) DB::connection($koneksi)->getDatabaseName();
        if (! str_contains(strtolower(basename($aktif)), 'dusk')) {
            throw new RuntimeException(
                "Database test harus database khusus Dusk (namanya mengandung kata 'dusk'). Aktif: {$aktif}. Test dihentikan."
            );
        }

        // Hanya menyentuh database Dusk di atas.
                // Hanya menyentuh database Dusk di atas.
        // DUSK_SIMPAN_DATA=true di .env.dusk.local: data test sebelumnya tidak dihapus (hanya menjalankan migrasi baru).
        $simpanData = filter_var(env('DUSK_SIMPAN_DATA', false), FILTER_VALIDATE_BOOL);
        Artisan::call($simpanData ? 'migrate' : 'migrate:fresh', ['--database' => $koneksi, '--force' => true]);

        if (! self::$duskServerVerified) {
            $this->pastikanServerMemakaiDatabaseDusk($aktif);
            self::$duskServerVerified = true;
        }
    }

    private function bersihkanSesiBrowser(): void
    {
        Browser::$baseUrl = method_exists($this, 'baseUrl')
            ? $this->baseUrl()
            : rtrim((string) config('app.url'), '/');

        $this->browse(function (Browser $browser) {
            $browser->visit('/');
            $browser->driver->manage()->deleteAllCookies();
        });
    }

    private function pastikanServerMemakaiDatabaseDusk(string $database): void
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
                $browser->driver->manage()->deleteAllCookies();
            });
        } catch (Throwable $e) {
            throw new RuntimeException(
                "Server aplikasi tidak memakai database Dusk ({$database}), atau server tidak dapat dijangkau (APP_URL). "
                .'Test dihentikan agar tidak menyentuh database lain. Periksa .env.dusk.local dan server yang sedang berjalan. '
                .'Detail: '.$e->getMessage(),
                0,
                $e,
            );
        } finally {
            $penanda->delete();
        }
    }
}
