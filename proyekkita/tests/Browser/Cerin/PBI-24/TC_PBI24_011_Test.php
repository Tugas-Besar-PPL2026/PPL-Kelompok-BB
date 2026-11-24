<?php

namespace Tests\Browser\Cerin\PBI24;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\KesepakatanDuskTestCase;

/**
 * TC-PBI24-011 — Boundary - Tenggat kemarin dan hari ini
 *
 * Jenis Test     : Boundary
 * Data disiapkan : Kandidat Diterima; draf kesepakatan sudah ada.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Tenggat kemarin ditolak ("Tenggat penyelesaian tidak boleh sebelum hari ini."); tenggat hari ini diterima dan terkirim.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-24/TC_PBI24_011_Test.php
 */
class TC_PBI24_011_Test extends KesepakatanDuskTestCase
{
    public function test_tc_pbi24_011(): void
    {
        $this->browse(function (Browser $browser) {
            $d = $this->siapkanKesepakatan($browser, 'draf');

            // ===== Skenario utama =====
            $this->masuk($browser, $d['umkm']);
            $this->bukaDrafLewatMenu($browser, $d['kesepakatan']);

            $isi = $this->isiValid();
            $isi['deadline'] = now()->subDay()->toDateString();
            $this->isiKesepakatan($browser, $isi);
            $this->klikKirim($browser);
            $browser->assertPathIs("/umkm/kesepakatan/{$d['kesepakatan']}/susun")
                ->assertSeeIn('@galat-tenggat', 'Tenggat penyelesaian tidak boleh sebelum hari ini.')
                ->assertSeeIn('@status-kesepakatan', 'Draf');
            $this->bukti($browser, 'TC_PBI24_011_kemarin');

            $isi['deadline'] = now()->toDateString();
            $this->isiKesepakatan($browser, $isi);
            $this->klikKirim($browser);
            $browser->assertSee('Kesepakatan berhasil dikirim ke kandidat.')
                ->assertSeeIn('@detail-kesepakatan @status-kesepakatan', 'Menunggu Persetujuan')
                ->assertSeeIn('@ringkasan-tenggat', $this->tanggalTampil(now()));
            $this->bukti($browser, 'TC_PBI24_011_hari_ini');
        });
    }
}
