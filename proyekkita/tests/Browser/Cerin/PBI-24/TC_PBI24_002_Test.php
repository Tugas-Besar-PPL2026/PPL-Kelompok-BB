<?php

namespace Tests\Browser\Cerin\PBI24;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\KesepakatanDuskTestCase;

/**
 * TC-PBI24-002 — Formulir menampilkan data proyek
 *
 * Jenis Test     : Positif
 * Data disiapkan : 1 proyek terbit (Rp2.500.000, 2 termin); kandidat Diterima; draf dibuat lewat tombol Susun Kesepakatan.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Data kandidat, label Lamaran Diterima, total kompensasi, jumlah termin, dan tabel termin tampil; data proyek tidak bisa diubah.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-24/TC_PBI24_002_Test.php
 */
class TC_PBI24_002_Test extends KesepakatanDuskTestCase
{
    public function test_tc_pbi24_002(): void
    {
        $this->browse(function (Browser $browser) {
            $d = $this->siapkanKesepakatan($browser, 'draf');

            // ===== Skenario utama =====
            $this->masuk($browser, $d['umkm']);
            $this->bukaDrafLewatMenu($browser, $d['kesepakatan']);
            $browser->assertSee('Susun Kesepakatan Pekerjaan')
                ->assertSeeIn('@kartu-kandidat', $d['kandidat']['nama'])
                ->assertSeeIn('@kartu-kandidat', 'Lamaran Diterima')
                ->assertInputValue('@nama-proyek', $d['proyek']['judul'])
                ->assertInputValue('@total-kompensasi', 'Rp2.500.000')
                ->assertInputValue('@jumlah-termin', '2 termin')
                ->assertSeeIn('@tabel-termin-kesepakatan', self::TERMIN_1)
                ->assertSeeIn('@tabel-termin-kesepakatan', self::TERMIN_2)
                ->assertSeeIn('@tabel-termin-kesepakatan', 'Rp1.250.000')
                ->assertAttribute('@nama-proyek', 'readonly', 'true')
                ->assertAttribute('@total-kompensasi', 'readonly', 'true')
                ->assertAttribute('@jumlah-termin', 'readonly', 'true');
            $this->assertSame(2, (int) $browser->script("return document.querySelectorAll('[dusk=\"tabel-termin-kesepakatan\"] tbody tr').length;")[0]);
            $this->bukti($browser, 'TC_PBI24_002');
        });
    }
}
