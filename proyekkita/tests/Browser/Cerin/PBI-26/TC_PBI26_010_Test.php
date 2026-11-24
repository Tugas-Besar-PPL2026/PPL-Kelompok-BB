<?php

namespace Tests\Browser\Cerin\PBI26;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\KesepakatanDuskTestCase;

/**
 * TC-PBI26-010 — Boundary - Pengguna tanpa kesepakatan
 *
 * Jenis Test     : Boundary
 * Data disiapkan : Akun kandidat baru tanpa lamaran maupun kesepakatan.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Semua tab berjumlah 0 dan pesan keadaan kosong tampil tanpa error.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-26/TC_PBI26_010_Test.php
 */
class TC_PBI26_010_Test extends KesepakatanDuskTestCase
{
    public function test_tc_pbi26_010(): void
    {
        $this->browse(function (Browser $browser) {
            $kandidat = $this->daftarKandidat($browser, 'Kandidat Baru');

            // ===== Skenario utama =====
            $this->masuk($browser, $kandidat);
            $this->bukaMenuKesepakatan($browser, 'kandidat');
            foreach (['active', 'sent', 'completed', 'closed'] as $tab) {
                $this->assertSame(0, $this->jumlahTab($browser, $tab), "Tab {$tab} seharusnya 0.");
            }
            $browser->assertPresent('@kosong')->assertDontSee('Server Error');
            $this->bukti($browser, 'TC_PBI26_010');
        });
    }
}
