<?php

namespace Tests\Browser\Cerin\PBI25;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\KesepakatanDuskTestCase;

/**
 * TC-PBI25-012 — Menanggapi kesepakatan Draf
 *
 * Jenis Test     : Negatif
 * Data disiapkan : Kesepakatan kandidat masih Draf (belum dikirim UMKM).
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Tidak tampil di tab Perlu Tanggapan; URL tanggapan menghasilkan halaman 404.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-25/TC_PBI25_012_Test.php
 */
class TC_PBI25_012_Test extends KesepakatanDuskTestCase
{
    public function test_tc_pbi25_012(): void
    {
        $this->browse(function (Browser $browser) {
            $d = $this->siapkanKesepakatan($browser, 'draf');

            // ===== Skenario utama =====
            $this->masuk($browser, $d['kandidat']);
            $this->bukaMenuKesepakatan($browser, 'kandidat');
            $this->klikTab($browser, 'sent');
            $this->assertSame(0, $this->jumlahTab($browser, 'sent'));
            $browser->assertMissing("@kartu-kesepakatan-{$d['kesepakatan']}");

            $browser->visit("/kandidat/kesepakatan/{$d['kesepakatan']}/tanggapi")
                ->assertSee('404')
                ->assertMissing('@form-tanggapan');
            $this->bukti($browser, 'TC_PBI25_012');
        });
    }
}
