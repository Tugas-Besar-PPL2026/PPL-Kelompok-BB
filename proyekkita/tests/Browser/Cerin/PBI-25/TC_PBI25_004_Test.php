<?php

namespace Tests\Browser\Cerin\PBI25;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\KesepakatanDuskTestCase;

/**
 * TC-PBI25-004 — Menolak tanpa alasan
 *
 * Jenis Test     : Negatif
 * Data disiapkan : Kesepakatan kandidat berstatus Dikirim.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Pesan "Alasan penolakan wajib diisi."; status tetap Dikirim (alasan kosong).
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-25/TC_PBI25_004_Test.php
 */
class TC_PBI25_004_Test extends KesepakatanDuskTestCase
{
    public function test_tc_pbi25_004(): void
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
            $this->isiTanggapan($browser, 'tolak', '');
            $this->klikKirimTanggapan($browser);
            $browser->assertPathIs("/kandidat/kesepakatan/{$d['kesepakatan']}/tanggapi")
                ->assertSeeIn('@galat-alasan', 'Alasan penolakan wajib diisi.')
                ->assertSeeIn('@status-kesepakatan', 'Menunggu Persetujuan Anda');
            $this->bukti($browser, 'TC_PBI25_004');
        });
    }
}
