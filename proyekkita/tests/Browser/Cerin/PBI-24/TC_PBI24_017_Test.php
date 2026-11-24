<?php

namespace Tests\Browser\Cerin\PBI24;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\KesepakatanDuskTestCase;

/**
 * TC-PBI24-017 — Mengubah kesepakatan yang sudah Dikirim
 *
 * Jenis Test     : Negatif
 * Data disiapkan : Kesepakatan kandidat sudah berstatus Dikirim.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Detail hanya ditampilkan tanpa kolom isian; tombol Simpan Draf dan Kirim ke Kandidat tidak tampil; URL susun dialihkan ke detail.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-24/TC_PBI24_017_Test.php
 */
class TC_PBI24_017_Test extends KesepakatanDuskTestCase
{
    public function test_tc_pbi24_017(): void
    {
        $this->browse(function (Browser $browser) {
            $d = $this->siapkanKesepakatan($browser, 'dikirim');

            // ===== Skenario utama =====
            $this->masuk($browser, $d['umkm']);
            $this->bukaMenuKesepakatan($browser, 'umkm');
            $this->klikTab($browser, 'sent');
            $browser->click("@buka-kesepakatan-{$d['kesepakatan']}")
                ->waitFor('@detail-kesepakatan', 20)
                ->assertSeeIn('@ringkasan-kesepakatan', self::RUANG_LINGKUP)
                ->assertMissing('@form-kesepakatan')
                ->assertMissing('@simpan-draf')
                ->assertMissing('@kirim-kesepakatan');
            $this->assertSame(0, (int) $browser->script("return document.querySelectorAll('[dusk=\"detail-kesepakatan\"] input, [dusk=\"detail-kesepakatan\"] textarea').length;")[0]);
            $this->bukti($browser, 'TC_PBI24_017');

            // Membuka URL susun secara langsung dialihkan ke detail
            $browser->visit("/umkm/kesepakatan/{$d['kesepakatan']}/susun")
                ->assertPathIs("/umkm/kesepakatan/{$d['kesepakatan']}")
                ->assertSee('Kesepakatan yang sudah dikirim tidak dapat diubah lagi.')
                ->assertMissing('@form-kesepakatan');
        });
    }
}
