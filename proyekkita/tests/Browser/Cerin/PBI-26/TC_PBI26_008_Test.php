<?php

namespace Tests\Browser\Cerin\PBI26;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\KesepakatanDuskTestCase;

/**
 * TC-PBI26-008 — Status berubah setelah disetujui
 *
 * Jenis Test     : Positif
 * Data disiapkan : Data lengkap Excel: UMKM punya Proyek A (kuota 4) dan Proyek B (kuota 1); K1: A Aktif + B Dikirim; K2: A Aktif; K3: A Ditolak ("Tenggat terlalu singkat"); K4: A Draf. Setelah itu Kandidat 1 menyetujui kesepakatan Proyek B.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Kesepakatan Proyek B pindah dari tab Dikirim ke Aktif; jumlah tab menjadi Dikirim (0) dan Aktif (3).
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-26/TC_PBI26_008_Test.php
 */
class TC_PBI26_008_Test extends KesepakatanDuskTestCase
{
    public function test_tc_pbi26_008(): void
    {
        $this->browse(function (Browser $browser) {
            $d = $this->siapkanDataLengkap($browser);
            $this->tanggapiSebagaiKandidat($browser, $d['k1a']['kandidat'], $d['k1b']['kesepakatan'], 'setujui');

            // ===== Skenario utama =====
            $this->masuk($browser, $d['umkm']);
            $this->bukaMenuKesepakatan($browser, 'umkm');
            $browser->refresh()->waitFor('@tab-kesepakatan', 20);
            $this->assertSame(0, $this->jumlahTab($browser, 'sent'));
            $this->assertSame(3, $this->jumlahTab($browser, 'active'));
            $this->klikTab($browser, 'active');
            $browser->assertPresent("@baris-kesepakatan-{$d['k1b']['kesepakatan']}");
            $this->klikTab($browser, 'sent');
            $browser->assertMissing("@baris-kesepakatan-{$d['k1b']['kesepakatan']}");
            $this->bukti($browser, 'TC_PBI26_008');
        });
    }
}
