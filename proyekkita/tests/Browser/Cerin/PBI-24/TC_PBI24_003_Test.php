<?php

namespace Tests\Browser\Cerin\PBI24;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\KesepakatanDuskTestCase;

/**
 * TC-PBI24-003 — Simpan draf dengan isi sebagian
 *
 * Jenis Test     : Positif
 * Data disiapkan : Kandidat Diterima; draf kesepakatan masih kosong.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Pesan "Draf kesepakatan berhasil disimpan.", status tetap Draf, ruang lingkup tetap tampil setelah dimuat ulang.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-24/TC_PBI24_003_Test.php
 */
class TC_PBI24_003_Test extends KesepakatanDuskTestCase
{
    public function test_tc_pbi24_003(): void
    {
        $this->browse(function (Browser $browser) {
            $d = $this->siapkanKesepakatan($browser, 'draf');

            // ===== Skenario utama =====
            $this->masuk($browser, $d['umkm']);
            $this->bukaDrafLewatMenu($browser, $d['kesepakatan']);
            $this->isiKesepakatan($browser, ['scope' => self::RUANG_LINGKUP]);
            $this->klikSimpanDraf($browser);
            $browser->assertSee('Draf kesepakatan berhasil disimpan.')
                ->assertSeeIn('@status-kesepakatan', 'Draf');

            $browser->refresh()->waitFor('@form-kesepakatan', 20)
                ->assertInputValue('@ruang-lingkup', self::RUANG_LINGKUP)
                ->assertInputValue('@hasil-diserahkan', '')
                ->assertSeeIn('@status-kesepakatan', 'Draf');
            $this->bukti($browser, 'TC_PBI24_003');
        });
    }
}
