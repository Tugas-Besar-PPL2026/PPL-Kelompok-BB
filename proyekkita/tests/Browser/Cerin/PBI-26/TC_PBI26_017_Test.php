<?php

namespace Tests\Browser\Cerin\PBI26;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\KesepakatanDuskTestCase;

/**
 * TC-PBI26-017 — Detail hanya baca
 *
 * Jenis Test     : Positif
 * Data disiapkan : Kesepakatan Kandidat 1 berstatus Aktif.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Ringkasan Kesepakatan tidak memiliki kolom isian atau tombol untuk mengubah isi kesepakatan.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-26/TC_PBI26_017_Test.php
 */
class TC_PBI26_017_Test extends KesepakatanDuskTestCase
{
    public function test_tc_pbi26_017(): void
    {
        $this->browse(function (Browser $browser) {
            $d = $this->siapkanKesepakatan($browser, 'aktif');

            // ===== Skenario utama =====
            $this->masuk($browser, $d['kandidat']);
            $this->bukaMenuKesepakatan($browser, 'kandidat');
            $this->klikTab($browser, 'active');
            $browser->click("@kartu-kesepakatan-{$d['kesepakatan']}")
                ->waitFor('@ringkasan-kesepakatan', 20);
            $jumlah = (int) $browser->script("return document.querySelectorAll('[dusk=\"ringkasan-kesepakatan\"] input, [dusk=\"ringkasan-kesepakatan\"] textarea, [dusk=\"ringkasan-kesepakatan\"] select, [dusk=\"ringkasan-kesepakatan\"] button, [dusk=\"ringkasan-kesepakatan\"] form').length;")[0];
            $this->assertSame(0, $jumlah);
            $browser->assertDontSee('Simpan Draf')->assertDontSee('Kirim ke Kandidat');
            $this->bukti($browser, 'TC_PBI26_017');
        });
    }
}
