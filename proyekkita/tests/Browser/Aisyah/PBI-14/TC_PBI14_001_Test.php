<?php

namespace Tests\Browser\Aisyah\PBI14;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EPbi131415;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

/**
 * TC-PBI14-001 (sheet PBI 14, PBI 13 14 15.xlsx).
 *
 * Alur E2E (semua lewat Chrome, tanpa loginAs/factory/seeder/SQL):
 *   Persiapan: UMKM "Warung Uji PBI14 [kode]" didaftarkan dan diverifikasi Admin, membuat proyek lewat form,
 *              Admin menyetujui (Dipublikasikan); satu akun kandidat didaftarkan.
 *   Langkah  : login kandidat, Eksplor Proyek, periksa kartu proyek uji.
 * Database MySQL test persistent hanya dibaca untuk verifikasi tambahan.
 */
#[Group('e2e-proyek')]
#[Group('pbi14')]
#[Group('TC-PBI14-001')]
class TC_PBI14_001_Test extends DuskTestCase
{
    use AlurE2EPbi131415;
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use UsesDuskTestDatabase;

    /**
     * TC-PBI14-001 - Daftar Eksplor Proyek menampilkan ringkasan proyek dan UMKM penyelenggara.
     */
    public function test_TC_PBI14_001_kartu_eksplor_menampilkan_ringkasan_proyek(): void
    {
        $this->browse(function (Browser $browser) {
            // Persiapan: proyek Dipublikasikan milik UMKM terverifikasi, akun kandidat terdaftar.
            $umkm = $this->siapkanUmkmBaru($browser, 'PBI14', $this->profilWarungUji());
            $data = $this->dataProyekPbi14($umkm['kode']);
            $proyekId = $this->terbitkanProyekUmkm($browser, $umkm, $data);
            $kandidat = $this->daftarKandidatBaru($browser, 'Kandidat PBI14');
            $this->keluar($browser);

            // Langkah 1-2: login kandidat, menu Eksplor Proyek.
            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaEksplor($browser);

            // Langkah 3: cari kartu proyek uji pada daftar.
            $kartu = $this->teksKartuEksplor($browser, $data['judul']);
            $this->assertNotNull($kartu, 'Kartu proyek uji tampil di Eksplor Proyek.');

            foreach ([
                $umkm['profil']['nama'],
                'UMKM Terverifikasi',
                'Bandung · Kuliner & Minuman',
                self::KAT_DESAIN,
                $data['deskripsi'],
                self::KET_KEMASAN,
                self::KET_FIGMA,
                'Rp2.500.000 · 1 termin',
                'Sisa kuota',
                '2 dari 2 orang',
                $this->tanggalIndonesia(now()->addDays(7)),
                'Lihat Detail & Lamar',
            ] as $teks) {
                $this->assertStringContainsString($teks, $kartu, "Kartu proyek memuat: {$teks}");
            }

            $browser->assertPresent("a[dusk='lihat-detail'][href$='/kandidat/proyek/{$proyekId}']");

            // Verifikasi tambahan di database (hanya membaca).
            $this->assertSame('published', $this->statusProyekDb($proyekId));
        });
    }
}
