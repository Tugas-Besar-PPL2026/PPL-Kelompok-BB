<?php

namespace Tests\Browser\Cerin\PBI26;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\KesepakatanDuskTestCase;

/**
 * TC-PBI26-009 — Boundary - Tab tanpa data
 *
 * Jenis Test     : Boundary
 * Data disiapkan : UMKM punya 1 kesepakatan Aktif dan tidak ada yang Selesai.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Tab Selesai berjumlah 0, pesan keadaan kosong tampil, tidak ada error.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-26/TC_PBI26_009_Test.php
 */
class TC_PBI26_009_Test extends KesepakatanDuskTestCase
{
    public function test_tc_pbi26_009(): void
    {
        $this->browse(function (Browser $browser) {
            $d = $this->siapkanKesepakatan($browser, 'aktif');

            // ===== Skenario utama =====
            $this->masuk($browser, $d['umkm']);
            $this->bukaMenuKesepakatan($browser, 'umkm');
            $this->klikTab($browser, 'completed');
            $this->assertSame(0, $this->jumlahTab($browser, 'completed'));
            $browser->assertPresent('@kosong')
                ->assertSeeIn('@kosong', 'Belum ada kesepakatan')
                ->assertDontSee('Server Error');
            $this->bukti($browser, 'TC_PBI26_009');
        });
    }
}
