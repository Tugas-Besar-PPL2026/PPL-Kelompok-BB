<?php

namespace Tests\Browser\Cerin\PBI26;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\KesepakatanDuskTestCase;

/**
 * TC-PBI26-003 — Berpindah tab
 *
 * Jenis Test     : Positif
 * Data disiapkan : Data lengkap Excel: UMKM punya Proyek A (kuota 4) dan Proyek B (kuota 1); K1: A Aktif + B Dikirim; K2: A Aktif; K3: A Ditolak ("Tenggat terlalu singkat"); K4: A Draf.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Setiap tab hanya berisi kesepakatan dengan status tab tersebut.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-26/TC_PBI26_003_Test.php
 */
class TC_PBI26_003_Test extends KesepakatanDuskTestCase
{
    public function test_tc_pbi26_003(): void
    {
        $this->browse(function (Browser $browser) {
            $d = $this->siapkanDataLengkap($browser);
            $harapan = [
                'draft' => [$d['k4']['kesepakatan']],
                'sent' => [$d['k1b']['kesepakatan']],
                'active' => [$d['k1a']['kesepakatan'], $d['k2']['kesepakatan']],
                'completed' => [],
                'closed' => [$d['k3']['kesepakatan']],
            ];

            // ===== Skenario utama =====
            $this->masuk($browser, $d['umkm']);
            $this->bukaMenuKesepakatan($browser, 'umkm');
            foreach ($harapan as $tab => $ids) {
                $this->klikTab($browser, $tab);
                $baris = $browser->script("return [...document.querySelectorAll('table.ks-table tbody tr')].map(tr => parseInt(tr.getAttribute('dusk').replace('baris-kesepakatan-', ''), 10));")[0] ?? [];
                sort($ids);
                sort($baris);
                $this->assertSame($ids, $baris, "Isi tab {$tab} tidak sesuai.");
                $this->bukti($browser, "TC_PBI26_003_{$tab}");
            }
        });
    }
}
