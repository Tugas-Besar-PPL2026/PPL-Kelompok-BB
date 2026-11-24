<?php

namespace Tests\Browser\Cerin\PBI24;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\KesepakatanDuskTestCase;

/**
 * TC-PBI24-016 — Mengirim ulang kesepakatan
 *
 * Jenis Test     : Negatif
 * Data disiapkan : Draf kesepakatan sudah terisi lengkap (Simpan Draf). Dua jendela browser (A dan B) login sebagai UMKM yang sama.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Jendela A mendapat pesan "Kesepakatan ini sudah dikirim dan tidak dapat dikirim ulang."; status tetap Dikirim; tanggal kirim tidak berubah.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-24/TC_PBI24_016_Test.php
 */
class TC_PBI24_016_Test extends KesepakatanDuskTestCase
{
    public function test_tc_pbi24_016(): void
    {
        $this->browse(function (Browser $jendelaA, Browser $jendelaB) {
            $d = $this->siapkanKesepakatan($jendelaA, 'draf');
            $this->simpanDrafSebagaiUmkm($jendelaA, $d['umkm'], $d['kesepakatan'], $this->isiValid());

            // ===== Skenario utama =====
            foreach ([$jendelaA, $jendelaB] as $jendela) {
                $this->masuk($jendela, $d['umkm']);
                $this->bukaDrafLewatMenu($jendela, $d['kesepakatan']);
            }

            // Jendela B mengirim lebih dulu
            $this->klikKirim($jendelaB);
            $jendelaB->assertSee('Kesepakatan berhasil dikirim ke kandidat.');
            $tanggalKirim = $this->teksRapi($jendelaB, '[dusk="tanggal-kirim"]');
            $this->bukti($jendelaB, 'TC_PBI24_016_jendelaB');

            // Jendela A (belum dimuat ulang) menekan Kirim ke Kandidat
            $this->klikKirim($jendelaA);
            $jendelaA->assertSee('Kesepakatan ini sudah dikirim dan tidak dapat dikirim ulang.')
                ->assertSeeIn('@detail-kesepakatan @status-kesepakatan', 'Menunggu Persetujuan');
            $this->assertSame($tanggalKirim, $this->teksRapi($jendelaA, '[dusk="tanggal-kirim"]'));
            $this->bukti($jendelaA, 'TC_PBI24_016_jendelaA');
        });
    }
}
