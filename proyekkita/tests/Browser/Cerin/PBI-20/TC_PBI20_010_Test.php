<?php

namespace Tests\Browser\Cerin\PBI20;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\CerinDuskTestCase;

/**
 * TC-PBI20-010 — Statistik, tab, dan badge status pada daftar pelamar diperbarui setelah pelamar dimasukkan ke Terpilih Sementara
 *
 * Jenis Test     : Positif
 * Data disiapkan : 1 proyek terbit kuota 2; K1 dan K2 melamar (Menunggu Keputusan), tidak ada status lain.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Kartu: Total 2, Menunggu 1, Terpilih sementara 1, Diterima 0 / 2. Tab: Semua (2), Menunggu Keputusan (1), Terpilih Sementara (1). Badge pelamar pertama 'Terpilih Sementara'. Tab Terpilih Sementara hanya menampilkan pelamar pertama. 'Kuota kandidat terisi 0 dari 2'.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-20/TC_PBI20_010_Test.php
 */
class TC_PBI20_010_Test extends CerinDuskTestCase
{
    public function test_tc_pbi20_010(): void
    {
        $this->browse(function (Browser $browser) {
            $umkm = $this->siapkanUmkmTerverifikasi($browser, 'UMKM A');
            $proyek = $this->siapkanProyekTerbit($browser, $umkm, 'Proyek', 2);
            $k1 = $this->daftarKandidat($browser, 'K1');
            $this->lamar($browser, $k1, $proyek);
            $k2 = $this->daftarKandidat($browser, 'K2');
            $this->lamar($browser, $k2, $proyek);

            // ===== Skenario utama =====
            $this->masuk($browser, $umkm);
            $this->bukaSeleksi($browser);
            $this->pilihProyek($browser, $proyek['id']);

            // Catat angka statistik awal
            $awal = $this->statistik($browser);
            $this->assertSame('2', $awal[1]);
            $this->assertSame('0', $awal[2]);

            // Klik Lihat pada pelamar pertama
            $namaPertama = $this->namaDiTabel($browser)[0];
            $lamaran = $this->lamaranIdDiTabel($browser, $namaPertama);
            $this->lihatPelamar($browser, $lamaran);
            $this->pilihKeputusan($browser, 'shortlist');
            $this->simpanKeputusan($browser);
            $this->kembaliKeDaftar($browser);

            $this->assertSame(['2', '1', '1', '0 / 2'], $this->statistik($browser));
            $tab = $this->tabStatus($browser);
            $this->assertContains('Semua 2', $tab);
            $this->assertContains('Menunggu Keputusan 1', $tab);
            $this->assertContains('Terpilih Sementara 1', $tab);
            $browser->assertSeeIn("@status-pelamar-{$lamaran}", 'Terpilih Sementara')
                ->assertSeeIn('@kuota-daftar', 'Kuota kandidat terisi 0 dari 2');
            $this->bukti($browser, 'TC_PBI20_010_daftar');

            $browser->waitForReload(fn (Browser $b) => $b->click('@tab-status a:nth-child(3)'), 20);
            $this->assertSame([$namaPertama], $this->namaDiTabel($browser));
            $this->bukti($browser, 'TC_PBI20_010_tab');
        });
    }
}
