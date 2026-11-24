<?php

namespace Tests\Browser\Cerin\PBI26;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\KesepakatanDuskTestCase;

/**
 * TC-PBI26-015 — Akses tanpa login
 *
 * Jenis Test     : Negatif
 * Data disiapkan : Kesepakatan Kandidat 1 berstatus Aktif.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Pengunjung yang belum login membuka URL detail kesepakatan: dialihkan ke /login.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-26/TC_PBI26_015_Test.php
 */
class TC_PBI26_015_Test extends KesepakatanDuskTestCase
{
    public function test_tc_pbi26_015(): void
    {
        $this->browse(function (Browser $browser) {
            $d = $this->siapkanKesepakatan($browser, 'aktif');

            // ===== Skenario utama =====
            $browser->visit('/login');
            $browser->driver->manage()->deleteAllCookies();
            $browser->visit("/kandidat/kesepakatan/{$d['kesepakatan']}")
                ->assertPathIs('/login')
                ->assertMissing('@detail-kesepakatan');
            $this->bukti($browser, 'TC_PBI26_015_kandidat');

            $browser->visit("/umkm/kesepakatan/{$d['kesepakatan']}")
                ->assertPathIs('/login');
            $this->bukti($browser, 'TC_PBI26_015_umkm');
        });
    }
}
