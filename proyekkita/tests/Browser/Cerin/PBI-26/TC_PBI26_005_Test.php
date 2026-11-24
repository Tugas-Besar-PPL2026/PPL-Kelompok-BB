<?php

namespace Tests\Browser\Cerin\PBI26;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\KesepakatanDuskTestCase;

/**
 * TC-PBI26-005 — Kandidat membuka detail
 *
 * Jenis Test     : Positif
 * Data disiapkan : Kesepakatan Kandidat 1 berstatus Aktif.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Detail dan label Aktif tampil; Ringkasan Kesepakatan sama dengan isi yang dikirim UMKM.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-26/TC_PBI26_005_Test.php
 */
class TC_PBI26_005_Test extends KesepakatanDuskTestCase
{
    public function test_tc_pbi26_005(): void
    {
        $this->browse(function (Browser $browser) {
            $d = $this->siapkanKesepakatan($browser, 'aktif');

            // ===== Skenario utama =====
            $this->masuk($browser, $d['kandidat']);
            $this->bukaMenuKesepakatan($browser, 'kandidat');
            $this->klikTab($browser, 'active');
            $browser->click("@kartu-kesepakatan-{$d['kesepakatan']}")
                ->waitFor('@detail-kesepakatan', 20)
                ->assertSeeIn('@detail-kesepakatan @status-kesepakatan', 'Aktif')
                ->assertSeeIn('@para-pihak', $d['umkm']['usaha'])
                ->assertSeeIn('@ringkasan-ruang-lingkup', self::RUANG_LINGKUP)
                ->assertSeeIn('@ringkasan-hasil', self::HASIL[0])
                ->assertSeeIn('@ringkasan-tenggat', $this->tanggalTampil($this->tenggat()))
                ->assertSeeIn('@ringkasan-kompensasi', 'Rp2.500.000 · 2 termin')
                ->assertSeeIn('@ringkasan-revisi', '2 kali');
            $this->bukti($browser, 'TC_PBI26_005');
        });
    }
}
