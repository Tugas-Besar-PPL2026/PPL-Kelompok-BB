<?php

namespace Tests\Browser\Cerin\PBI26;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\KesepakatanDuskTestCase;

/**
 * TC-PBI26-007 — Kandidat membuka kesepakatan Dikirim
 *
 * Jenis Test     : Positif
 * Data disiapkan : Kesepakatan Kandidat 1 berstatus Dikirim.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Kartu di tab Perlu Tanggapan membuka halaman Tawaran Kesepakatan Pekerjaan (PBI-25) beserta formulir Tanggapan Anda.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-26/TC_PBI26_007_Test.php
 */
class TC_PBI26_007_Test extends KesepakatanDuskTestCase
{
    public function test_tc_pbi26_007(): void
    {
        $this->browse(function (Browser $browser) {
            $d = $this->siapkanKesepakatan($browser, 'dikirim');

            // ===== Skenario utama =====
            $this->masuk($browser, $d['kandidat']);
            $this->bukaMenuKesepakatan($browser, 'kandidat');
            $this->klikTab($browser, 'sent');
            $browser->click("@kartu-kesepakatan-{$d['kesepakatan']}")
                ->waitForLocation("/kandidat/kesepakatan/{$d['kesepakatan']}/tanggapi", 20)
                ->assertSee('Tawaran Kesepakatan Pekerjaan')
                ->assertPresent('@panel-tanggapan')
                ->assertPresent('@kirim-tanggapan');
            $this->bukti($browser, 'TC_PBI26_007');
        });
    }
}
