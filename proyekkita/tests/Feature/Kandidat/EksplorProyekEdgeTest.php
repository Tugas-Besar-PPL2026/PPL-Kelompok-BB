<?php

namespace Tests\Feature\Kandidat;

use Laravel\Dusk\Browser;
use Tests\Browser\Concerns\AlurProyekKita;
use Tests\DuskTestCase;

/**
 * [PBI-15] TC-1 Pencarian proyek berdasarkan kata kunci pada judul.
 *
 * Prasyarat:
 * - Database Dusk terpisah dari database development.
 * - Master data kategori dan keterampilan tersedia.
 *
 * Input:
 * - Kata kunci: Logo Kedai Kopi
 *
 * Expected:
 * - Proyek dengan judul yang sesuai ditampilkan.
 * - Proyek lain tidak ditampilkan.
 * - Jumlah hasil menunjukkan 1 proyek ditemukan.
 * - Jumlah filter aktif menunjukkan 1 filter aktif.
 */
class PBI15TestCase1Test extends DuskTestCase
{
    use AlurProyekKita;

    public function test_pencarian_proyek_berdasarkan_judul(): void
    {
        $s = $this->akhiran();

        $this->browse(function (
            Browser $umkm,
            Browser $admin,
            Browser $kandidat
        ) use ($s) {

            // 1. Membuat katalog proyek melalui browser.
            $j = $this->bangunKatalog($umkm, $admin, $s);

            $logo = $j['logo'];
            $poster = $j['rekap'];
            $katalog = $j['katalog'];

            // 2. Mendaftarkan akun kandidat melalui halaman registrasi.
            $this->daftarAkun(
                $kandidat,
                "Kandidat PBI15 {$s}",
                "kandidat1.pbi15.{$s}@example.test",
                'kandidat'
            );

            // 3. Mencari proyek berdasarkan kata kunci judul.
            $kataKunci = "Logo Kedai Kopi {$s}";

            $this->terapkanFilter($kandidat, [
                'cari' => $kataKunci,
            ]);

            // 4. Memastikan proyek yang sesuai ditampilkan.
            $kandidat->assertSee($logo);

            // 5. Memastikan proyek lain tidak ditampilkan.
            $kandidat->assertDontSee($poster)
                ->assertDontSee($katalog);

            // 6. Memastikan jumlah hasil pencarian sesuai.
            $kandidat->assertSee('1 proyek ditemukan')
                ->assertSee('1 filter aktif');

            // 7. Memastikan kata kunci tersimpan pada form.
            $kandidat->assertInputValue('cari', $kataKunci);

            // 8. Menyimpan bukti screenshot.
            $kandidat->screenshot("pbi15-tc1-{$s}");
        });
    }
}
