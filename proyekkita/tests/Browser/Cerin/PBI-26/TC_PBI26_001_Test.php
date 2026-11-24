<?php

namespace Tests\Browser\Cerin\PBI26;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\KesepakatanDuskTestCase;

/**
 * TC-PBI26-001 — Daftar kesepakatan UMKM
 *
 * Jenis Test     : Positif
 * Data disiapkan : Data lengkap Excel: UMKM punya Proyek A (kuota 4) dan Proyek B (kuota 1); K1: A Aktif + B Dikirim; K2: A Aktif; K3: A Ditolak ("Tenggat terlalu singkat"); K4: A Draf.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Tab Draf (1), Dikirim (1), Aktif (2), Selesai (0), Ditolak/Dibatalkan (1); tabel berisi kolom ID, Kandidat & Proyek, Tenggat, Status, dan tombol Buka.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-26/TC_PBI26_001_Test.php
 */
class TC_PBI26_001_Test extends KesepakatanDuskTestCase
{
    public function test_tc_pbi26_001(): void
    {
        $this->browse(function (Browser $browser) {
            $d = $this->siapkanDataLengkap($browser);

            // ===== Skenario utama =====
            $this->masuk($browser, $d['umkm']);
            $this->bukaMenuKesepakatan($browser, 'umkm');
            $this->assertSame(
                ['draft' => 1, 'sent' => 1, 'active' => 2, 'completed' => 0, 'closed' => 1],
                collect(['draft', 'sent', 'active', 'completed', 'closed'])->mapWithKeys(fn ($t) => [$t => $this->jumlahTab($browser, $t)])->all()
            );
            $kolom = $browser->script("return [...document.querySelectorAll('table.ks-table thead th')].map(th => th.innerText.trim().toUpperCase());")[0];
            foreach (['ID', 'KANDIDAT & PROYEK', 'TENGGAT', 'STATUS'] as $judul) {
                $this->assertContains($judul, $kolom);
            }
            $browser->assertPresent("@buka-kesepakatan-{$d['k1a']['kesepakatan']}")
                ->assertPresent("@buka-kesepakatan-{$d['k2']['kesepakatan']}");
            $this->bukti($browser, 'TC_PBI26_001');
        });
    }
}
