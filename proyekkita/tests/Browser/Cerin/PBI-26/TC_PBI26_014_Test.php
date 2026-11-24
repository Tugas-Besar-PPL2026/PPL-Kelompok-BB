<?php

namespace Tests\Browser\Cerin\PBI26;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\KesepakatanDuskTestCase;

/**
 * TC-PBI26-014 — Akses oleh Admin
 *
 * Jenis Test     : Negatif
 * Data disiapkan : Akun Admin tersedia (DUSK_ADMIN_EMAIL).
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Admin membuka halaman Kesepakatan & Progres UMKM maupun Kandidat: halaman 403, daftar tidak tampil.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-26/TC_PBI26_014_Test.php
 */
class TC_PBI26_014_Test extends KesepakatanDuskTestCase
{
    public function test_tc_pbi26_014(): void
    {
        $this->browse(function (Browser $browser) {
            // ===== Skenario utama =====
            $this->masuk($browser, $this->akunAdmin());
            $browser->visit('/umkm/kesepakatan')
                ->assertSee('403')
                ->assertMissing('@tab-kesepakatan');
            $this->bukti($browser, 'TC_PBI26_014_umkm');

            $browser->visit('/kandidat/kesepakatan')
                ->assertSee('403')
                ->assertMissing('@tab-kesepakatan');
            $this->bukti($browser, 'TC_PBI26_014_kandidat');
        });
    }
}
