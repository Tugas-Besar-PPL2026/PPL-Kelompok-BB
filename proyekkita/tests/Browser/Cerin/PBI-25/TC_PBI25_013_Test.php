<?php

namespace Tests\Browser\Cerin\PBI25;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\KesepakatanDuskTestCase;

/**
 * TC-PBI25-013 — Menanggapi ulang kesepakatan Aktif
 *
 * Jenis Test     : Negatif
 * Data disiapkan : Kesepakatan kandidat berstatus Dikirim. Dua jendela browser (A dan B) login sebagai kandidat yang sama.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Jendela A mendapat pesan "Kesepakatan ini sudah ditanggapi."; status tetap Aktif.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-25/TC_PBI25_013_Test.php
 */
class TC_PBI25_013_Test extends KesepakatanDuskTestCase
{
    public function test_tc_pbi25_013(): void
    {
        $this->browse(function (Browser $jendelaA, Browser $jendelaB) {
            $d = $this->siapkanKesepakatan($jendelaA, 'dikirim');

            // ===== Skenario utama =====
            foreach ([$jendelaA, $jendelaB] as $jendela) {
                $this->masuk($jendela, $d['kandidat']);
                $this->bukaMenuKesepakatan($jendela, 'kandidat');
                $this->klikTab($jendela, 'sent');
                $jendela->click("@kartu-kesepakatan-{$d['kesepakatan']}")->waitFor('@form-tanggapan', 20);
            }

            // Jendela B menanggapi lebih dulu
            $this->isiTanggapan($jendelaB, 'setujui', '', true);
            $this->klikKirimTanggapan($jendelaB);
            $jendelaB->assertSee('Tanggapan berhasil dikirim.');
            $tanggalB = $this->teksRapi($jendelaB, '[dusk="tanggal-disetujui"]');
            $this->bukti($jendelaB, 'TC_PBI25_013_jendelaB');

            // Jendela A (belum dimuat ulang) menanggapi kebalikannya
            $this->isiTanggapan($jendelaA, 'tolak', 'berubah pikiran', false);
            $this->klikKirimTanggapan($jendelaA);
            $jendelaA->assertSee('Kesepakatan ini sudah ditanggapi.')
                ->assertSeeIn('@detail-kesepakatan @status-kesepakatan', 'Aktif');
            $this->assertSame($tanggalB, $this->teksRapi($jendelaA, '[dusk="tanggal-disetujui"]'));
            $this->bukti($jendelaA, 'TC_PBI25_013_jendelaA');
        });
    }
}
