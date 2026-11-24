<?php

namespace Tests\Browser\Cerin\PBI24;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\KesepakatanDuskTestCase;

/**
 * TC-PBI24-001 — Menyusun draf dari pelamar Diterima
 *
 * Jenis Test     : Positif
 * Data disiapkan : 1 proyek terbit (Rp2.500.000, 2 termin); 1 kandidat melamar lalu Diterima, belum ada kesepakatan.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Halaman Susun Kesepakatan terbuka untuk kandidat; tab Draf berisi 1 kesepakatan berstatus Draf.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-24/TC_PBI24_001_Test.php
 */
class TC_PBI24_001_Test extends KesepakatanDuskTestCase
{
    public function test_tc_pbi24_001(): void
    {
        $this->browse(function (Browser $browser) {
            $d = $this->siapkanPelamarDiterima($browser);

            // ===== Skenario utama =====
            $this->masuk($browser, $d['umkm']);
            $this->bukaSeleksi($browser);
            $this->pilihProyek($browser, $d['proyek']['id']);
            $this->lihatPelamar($browser, $d['lamaran']);
            $browser->click('@susun-kesepakatan')
                ->waitFor('@halaman-susun-kesepakatan', 20)
                ->assertSee('Susun Kesepakatan Pekerjaan')
                ->assertSeeIn('@nama-kandidat', $d['kandidat']['nama'])
                ->assertSeeIn('@status-kesepakatan', 'Draf');
            $id = $this->idKesepakatanDariUrl($browser);

            $this->bukaMenuKesepakatan($browser, 'umkm');
            $this->klikTab($browser, 'draft');
            $this->assertSame(1, $this->jumlahTab($browser, 'draft'));
            $browser->assertSeeIn("@baris-kesepakatan-{$id}", $d['kandidat']['nama'])
                ->assertSeeIn("@baris-kesepakatan-{$id}", 'Draf');
            $this->bukti($browser, 'TC_PBI24_001');
        });
    }
}
