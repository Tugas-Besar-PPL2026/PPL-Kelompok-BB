<?php

namespace Tests\Browser\Cerin\PBI19;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\CerinDuskTestCase;

/**
 * TC-PBI19-009 — UMKM belum memiliki proyek terbit
 *
 * Jenis Test     : Negatif
 * Data disiapkan : UMKM C didaftarkan dan Profil Usaha dilengkapi, tanpa proyek.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Tampil 'Belum ada proyek yang menerima pelamar' dan tombol 'Buat Proyek'.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-19/TC_PBI19_009_Test.php
 */
class TC_PBI19_009_Test extends CerinDuskTestCase
{
    public function test_tc_pbi19_009(): void
    {
        $this->browse(function (Browser $browser) {
            $umkmC = $this->daftarUmkm($browser, 'UMKM C');

            // ===== Skenario utama =====
            $this->masuk($browser, $umkmC);
            $this->bukaSeleksi($browser);
            $browser->assertSee('Belum ada proyek yang menerima pelamar')
                ->assertSeeLink('Buat Proyek')
                ->assertMissing('#pilih-proyek');
            $this->bukti($browser, 'TC_PBI19_009');
        });
    }
}
