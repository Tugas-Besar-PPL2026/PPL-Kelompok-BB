<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use RuntimeException;

/**
 * Pengaman database untuk Laravel Dusk (database test persistent).
 *
 * Masalah yang dicegah: test Dusk menulis ke database yang dipakai untuk development.
 *
 * Yang dilakukan trait ini (otomatis sebelum setiap test):
 *  1. Menolak berjalan bila APP_ENV = production.
 *  2. Memakai koneksi database dari .env.dusk.local:
 *     - MySQL: nama database WAJIB mengandung kata "dusk" (contoh: proyekkita_dusk).
 *     - SQLite: selalu dipaksa memakai file database/dusk.sqlite.
 *     Selain itu test dihentikan, sehingga database development tidak pernah tersentuh.
 *  3. Menjalankan "migrate" biasa saja (menambah tabel/kolom baru). TIDAK pernah menjalankan migrate:fresh,
 *     truncate, atau menghapus data: database test bersifat persistent dan dipakai ulang antar-run.
 *
 * Server aplikasi yang dibuka browser harus memakai database yang sama (.env.dusk.local). Trait ini tidak lagi
 * memeriksanya lewat login Admin di browser. Helper E2E (AlurE2EProyekKita) tetap memeriksa hal itu saat akun baru
 * didaftarkan lewat UI.
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

    /** Dipanggil otomatis oleh Laravel (setUp + nama trait) setelah aplikasi dibuat. */
    public function setUpUsesDuskTestDatabase(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('Test Dusk tidak boleh dijalankan di environment production.');
        }

        $this->siapkanPengaturanBrowser();
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

        // Hanya menyentuh database Dusk di atas, dan hanya migrasi baru (data lama tidak dihapus).
        Artisan::call('migrate', ['--database' => $koneksi, '--force' => true]);
    }

    /**
     * Trait ini berjalan di tahap setUp awal, sebelum DuskTestCase menetapkan pengaturan Browser.
     * Tetapkan sendiri dengan nilai yang sama agar pembukaan browser di sini tidak gagal.
     */
    private function siapkanPengaturanBrowser(): void
    {
        Browser::$baseUrl = method_exists($this, 'baseUrl')
            ? $this->baseUrl()
            : rtrim((string) config('app.url'), '/');
        Browser::$storeScreenshotsAt = base_path('tests/Browser/screenshots');
        Browser::$storeConsoleLogAt = base_path('tests/Browser/console');
    }

    private function bersihkanSesiBrowser(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/');
            $browser->driver->manage()->deleteAllCookies();
        });
    }
}
