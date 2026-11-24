<?php

namespace Tests\Browser\Cerin\PBI26;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\KesepakatanDuskTestCase;

/**
 * TC-PBI26-006 — Detail kesepakatan Ditolak
 *
 * Jenis Test     : Positif
 * Data disiapkan : Kesepakatan Kandidat 3 ditolak dengan alasan "Tenggat terlalu singkat".
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : UMKM melihat label Ditolak dan alasan "Tenggat terlalu singkat".
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-26/TC_PBI26_006_Test.php
 */
class TC_PBI26_006_Test extends KesepakatanDuskTestCase
{
    public function test_tc_pbi26_006(): void
    {
        $this->browse(function (Browser $browser) {
            $d = $this->siapkanKesepakatan($browser, 'ditolak', 2, 'K3', 'Tenggat terlalu singkat');

            // ===== Skenario utama =====
            $this->masuk($browser, $d['umkm']);
            $this->bukaMenuKesepakatan($browser, 'umkm');
            $this->klikTab($browser, 'closed');
            $browser->click("@buka-kesepakatan-{$d['kesepakatan']}")
                ->waitFor('@detail-kesepakatan', 20)
                ->assertSeeIn('@detail-kesepakatan @status-kesepakatan', 'Ditolak')
                ->assertSeeIn('@alasan-penolakan', 'Tenggat terlalu singkat');
            $this->bukti($browser, 'TC_PBI26_006');
        });
    }
}
