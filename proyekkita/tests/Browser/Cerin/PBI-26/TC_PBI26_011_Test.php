<?php

namespace Tests\Browser\Cerin\PBI26;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\KesepakatanDuskTestCase;

/**
 * TC-PBI26-011 — Draf tidak tampil bagi kandidat
 *
 * Jenis Test     : Negatif
 * Data disiapkan : Kesepakatan Kandidat 4 masih Draf.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Kandidat 4 tidak melihat kesepakatan Draf di tab mana pun; semua tab berjumlah 0.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-26/TC_PBI26_011_Test.php
 */
class TC_PBI26_011_Test extends KesepakatanDuskTestCase
{
    public function test_tc_pbi26_011(): void
    {
        $this->browse(function (Browser $browser) {
            $d = $this->siapkanKesepakatan($browser, 'draf', 2, 'K4');

            // ===== Skenario utama =====
            $this->masuk($browser, $d['kandidat']);
            $this->bukaMenuKesepakatan($browser, 'kandidat');
            foreach (['active', 'sent', 'completed', 'closed'] as $tab) {
                $this->klikTab($browser, $tab);
                $this->assertSame(0, $this->jumlahTab($browser, $tab), "Tab {$tab} seharusnya 0.");
                $browser->assertMissing("@kartu-kesepakatan-{$d['kesepakatan']}");
            }
            $this->bukti($browser, 'TC_PBI26_011');
        });
    }
}
