<?php

namespace Tests\Browser\Cerin\PBI25;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\KesepakatanDuskTestCase;

/**
 * TC-PBI25-001 — Melihat tawaran kesepakatan
 *
 * Jenis Test     : Positif
 * Data disiapkan : Kesepakatan kandidat berstatus Dikirim (tenggat 15-12-2026, batas revisi 2, Rp2.500.000, 2 termin).
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Halaman Tawaran Kesepakatan Pekerjaan tampil dengan label "Menunggu Persetujuan Anda"; isi kesepakatan tampil dan tidak bisa diubah.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-25/TC_PBI25_001_Test.php
 */
class TC_PBI25_001_Test extends KesepakatanDuskTestCase
{
    public function test_tc_pbi25_001(): void
    {
        $this->browse(function (Browser $browser) {
            $d = $this->siapkanKesepakatan($browser, 'dikirim');

            // ===== Skenario utama =====
            $this->masuk($browser, $d['kandidat']);
            $this->bukaMenuKesepakatan($browser, 'kandidat');
            $this->klikTab($browser, 'sent');
            $browser->click("@kartu-kesepakatan-{$d['kesepakatan']}")
                ->waitForLocation("/kandidat/kesepakatan/{$d['kesepakatan']}/tanggapi", 20)
                ->waitFor('@form-tanggapan', 20);
            $browser->assertSee('Tawaran Kesepakatan Pekerjaan')
                ->assertSeeIn('@status-kesepakatan', 'Menunggu Persetujuan Anda')
                ->assertSeeIn('@judul-proyek', $d['proyek']['judul'])
                ->assertSeeIn('@ruang-lingkup', self::RUANG_LINGKUP)
                ->assertSeeIn('@hasil-diserahkan', self::HASIL[0])
                ->assertSeeIn('@hasil-diserahkan', self::HASIL[2])
                ->assertSeeIn('@tenggat', $this->tanggalTampil($this->tenggat()))
                ->assertSeeIn('@total-kompensasi', 'Rp2.500.000')
                ->assertSeeIn('@batas-revisi', '2 kali')
                ->assertSeeIn('@tabel-termin-kesepakatan', self::TERMIN_1)
                ->assertSeeIn('@tabel-termin-kesepakatan', self::TERMIN_2);
            $this->assertSame(0, (int) $browser->script("return document.querySelectorAll('[dusk=\"isi-tawaran\"] input, [dusk=\"isi-tawaran\"] textarea').length;")[0]);
            $this->bukti($browser, 'TC_PBI25_001');
        });
    }
}
