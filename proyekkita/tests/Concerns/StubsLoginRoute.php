<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\Route;

/**
 * Menyediakan route "login" sementara KHUSUS di dalam sebuah test.
 *
 * Di repository ini route bernama "login" hanya didefinisikan di routes/dev.php dan hanya dimuat saat
 * environment local/dusk.local (autentikasi FR-01 / PBI-01 belum ada). Pada environment testing, tamu yang
 * membuka halaman ber-middleware "auth" akan memicu RouteNotFoundException, bukan pengalihan ke login.
 *
 * Trait ini mendaftarkan route pengganti hanya untuk test yang memanggilnya, tanpa mengubah file aplikasi.
 * Route hilang sendiri setelah test selesai karena aplikasi dibuat ulang untuk setiap test.
 */
trait StubsLoginRoute
{
    protected function stubLoginRoute(): void
    {
        if (! Route::has('login')) {
            Route::get('login', fn () => 'halaman login (stub test)')->name('login');
            app('router')->getRoutes()->refreshNameLookups();
        }
    }
}
