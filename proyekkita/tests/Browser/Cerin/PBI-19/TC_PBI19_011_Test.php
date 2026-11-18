<?php

namespace Tests\Browser\Cerin\PBI19;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\CerinDuskTestCase;

/**
 * TC-PBI19-011 — Melihat detail pelamar lengkap
 *
 * Jenis Test     : Positif
 * Data disiapkan : 1 proyek terbit; K1 mengisi Data Diri, keterampilan yang dibutuhkan proyek, pengalaman, dan portofolio, lalu melamar dengan pesan lamaran.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Tampil nama, headline, universitas, jurusan, kota domisili, ringkasan diri, pesan lamaran, keterampilan (yang dibutuhkan proyek lebih tebal), jenis pekerjaan + durasi pengalaman, portofolio, riwayat status, dan 'Belum ada penilaian · 0 proyek selesai'.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-19/TC_PBI19_011_Test.php
 */
class TC_PBI19_011_Test extends CerinDuskTestCase
{
    public function test_tc_pbi19_011(): void
    {
        $this->browse(function (Browser $browser) {
            $umkm = $this->siapkanUmkmTerverifikasi($browser, 'UMKM A');
            $proyek = $this->siapkanProyekTerbit($browser, $umkm, 'Proyek', 2);
            $k1 = $this->daftarKandidat($browser, 'K1', [
                'keterampilan' => $proyek['keterampilan'],
                'pengalaman' => true,
                'portofolio' => [['judul' => 'Katalog Produk UMKM', 'tautan' => 'https://contoh.proyekkita.test/katalog']],
            ]);
            $pesan = $this->pesanLamaran('K1');
            $this->lamar($browser, $k1, $proyek, $pesan);

            // ===== Skenario utama =====
            $this->masuk($browser, $umkm);
            $this->bukaSeleksi($browser);
            $this->pilihProyek($browser, $proyek['id']);
            $this->lihatPelamar($browser, $this->lamaranIdDiTabel($browser, $k1['nama']));

            $d = $k1['data'];
            $browser->assertSeeIn('.prof h2', $d['full_name'])
                ->assertSeeIn('.prof .hl', $d['headline'])
                ->assertSeeIn('.prof .meta3', $d['university'])
                ->assertSeeIn('.prof .meta3', $d['major'])
                ->assertSeeIn('.prof .meta3', $d['city'])
                ->assertSee($d['summary'])
                ->assertSee('Pesan Lamaran')
                ->assertSee($pesan)
                ->assertSee($d['pengalaman']['work_type'])
                ->assertSee($d['pengalaman']['duration'])
                ->assertSee('Katalog Produk UMKM')
                ->assertPresent('.tl .it')
                ->assertSee('Belum ada penilaian · 0 proyek selesai');

            // Keterampilan yang dibutuhkan proyek tampil lebih tebal (kelas tag req)
            $tebal = $browser->script("return [...document.querySelectorAll('.tag.req')].map(e => e.innerText.trim());")[0];
            $this->assertContains($d['keterampilan'], $tebal);
            $this->bukti($browser, 'TC_PBI19_011');
        });
    }
}
