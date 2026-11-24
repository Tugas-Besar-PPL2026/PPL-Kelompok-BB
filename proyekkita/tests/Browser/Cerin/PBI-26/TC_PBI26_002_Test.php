<?php

namespace Tests\Browser\Cerin\PBI26;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\KesepakatanDuskTestCase;

/**
 * TC-PBI26-002 — Daftar kesepakatan Kandidat
 *
 * Jenis Test     : Positif
 * Data disiapkan : Data lengkap Excel: UMKM punya Proyek A (kuota 4) dan Proyek B (kuota 1); K1: A Aktif + B Dikirim; K2: A Aktif; K3: A Ditolak ("Tenggat terlalu singkat"); K4: A Draf.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Kandidat 1 melihat tab Aktif (1), Perlu Tanggapan (1), Selesai (0), Ditolak/Dibatalkan (0); kartu berisi judul proyek, nama UMKM, tenggat, dan status.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-26/TC_PBI26_002_Test.php
 */
class TC_PBI26_002_Test extends KesepakatanDuskTestCase
{
    public function test_tc_pbi26_002(): void
    {
        $this->browse(function (Browser $browser) {
            $d = $this->siapkanDataLengkap($browser);

            // ===== Skenario utama =====
            $this->masuk($browser, $d['k1a']['kandidat']);
            $this->bukaMenuKesepakatan($browser, 'kandidat');
            $this->assertSame(
                ['active' => 1, 'sent' => 1, 'completed' => 0, 'closed' => 0],
                collect(['active', 'sent', 'completed', 'closed'])->mapWithKeys(fn ($t) => [$t => $this->jumlahTab($browser, $t)])->all()
            );
            $kartu = "@kartu-kesepakatan-{$d['k1a']['kesepakatan']}";
            $browser->assertSeeIn($kartu, $d['proyekA']['judul'])
                ->assertSeeIn($kartu, $d['umkm']['usaha'])
                ->assertSeeIn($kartu, $this->tanggalTampil($this->tenggat()))
                ->assertSeeIn($kartu, 'Aktif');
            $this->bukti($browser, 'TC_PBI26_002');
        });
    }
}
