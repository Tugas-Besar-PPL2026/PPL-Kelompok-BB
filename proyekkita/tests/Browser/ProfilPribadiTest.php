<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Laravel\Dusk\Browser;
use Tests\Concerns\BuildsApplicationScenario;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

/**
 * [PBI-02 / FR-03] Profil pribadi dan akademik melalui browser.
 * Kode skenario D02-xx dipakai di laporan pengujian. Database: lihat UsesDuskTestDatabase.
 */
class ProfilPribadiTest extends DuskTestCase
{
    use BuildsApplicationScenario, UsesDuskTestDatabase;

    /** D02-01 */
    public function test_kandidat_memperbarui_data_diri(): void
    {
        $kandidat = $this->makeKandidat();

        $this->browse(function (Browser $browser) use ($kandidat) {
            $browser->loginAs($kandidat)
                ->visit(route('kandidat.profil.datadiri'))
                ->type('@headline', 'Desainer UI untuk UMKM')
                ->type('@universitas', 'Universitas Telkom')
                ->press('@simpan-profil')
                ->waitForText('Data diri berhasil disimpan.')
                ->assertInputValue('@headline', 'Desainer UI untuk UMKM')
                ->assertInputValue('@universitas', 'Universitas Telkom');
        });
    }

    /** D02-02 */
    public function test_isian_wajib_kosong_menampilkan_pesan_validasi(): void
    {
        $kandidat = $this->makeKandidat();

        $this->browse(function (Browser $browser) use ($kandidat) {
            $browser->loginAs($kandidat)
                ->visit(route('kandidat.profil.datadiri'))
                ->clear('@nama-lengkap')
                ->clear('@ringkasan')
                ->press('@simpan-profil')
                ->waitFor('@galat-nama-lengkap')
                ->assertSeeIn('@galat-nama-lengkap', 'Nama lengkap wajib diisi.')
                ->assertSeeIn('@galat-ringkasan', 'Ringkasan diri wajib diisi.');
        });
    }

    /** D02-03 */
    public function test_headline_lebih_dari_80_karakter_ditolak(): void
    {
        $kandidat = $this->makeKandidat();

        $this->browse(function (Browser $browser) use ($kandidat) {
            $browser->loginAs($kandidat)
                ->visit(route('kandidat.profil.datadiri'))
                ->type('@headline', str_repeat('a', 81))
                ->press('@simpan-profil')
                ->waitFor('@galat-headline')
                ->assertSeeIn('@galat-headline', 'Headline maksimal 80 karakter.');
        });
    }

    /** D02-04 (boundary: tepat 80 karakter diterima) */
    public function test_headline_tepat_80_karakter_diterima(): void
    {
        $kandidat = $this->makeKandidat();

        $this->browse(function (Browser $browser) use ($kandidat) {
            $browser->loginAs($kandidat)
                ->visit(route('kandidat.profil.datadiri'))
                ->type('@headline', str_repeat('b', 80))
                ->press('@simpan-profil')
                ->waitForText('Data diri berhasil disimpan.')
                ->assertInputValue('@headline', str_repeat('b', 80));
        });
    }

    /** D02-05 (data tetap ada setelah halaman dimuat ulang) */
    public function test_data_tersimpan_tetap_tampil_setelah_halaman_dimuat_ulang(): void
    {
        $kandidat = $this->makeKandidat();

        $this->browse(function (Browser $browser) use ($kandidat) {
            $browser->loginAs($kandidat)
                ->visit(route('kandidat.profil.datadiri'))
                ->type('@domisili', 'Kabupaten Bandung')
                ->type('@program-studi', 'S1 Teknik Informatika')
                ->type('@ringkasan', 'Ringkasan baru untuk diuji.')
                ->press('@simpan-profil')
                ->waitForText('Data diri berhasil disimpan.')
                ->visit(route('kandidat.profil.datadiri'))
                ->assertInputValue('@domisili', 'Kabupaten Bandung')
                ->assertInputValue('@program-studi', 'S1 Teknik Informatika')
                ->assertInputValue('@ringkasan', 'Ringkasan baru untuk diuji.');
        });
    }

    /** D02-06 (unggah foto JPG/PNG kecil) */
    public function test_kandidat_mengunggah_foto_profil(): void
    {
        $kandidat = $this->makeKandidat();
        $berkas = tempnam(sys_get_temp_dir(), 'foto').'.png';
        // PNG 1x1 piksel, supaya tidak butuh ekstensi GD di mesin pengujian.
        file_put_contents($berkas, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));

        try {
            $this->browse(function (Browser $browser) use ($kandidat, $berkas) {
                $browser->loginAs($kandidat)
                    ->visit(route('kandidat.profil.datadiri'))
                    ->assertMissing('@foto-tampil')
                    ->attach('@foto-profil', $berkas)
                    ->press('@simpan-profil')
                    ->waitForText('Data diri berhasil disimpan.')
                    ->assertPresent('@foto-tampil');
            });
        } finally {
            // Bersihkan berkas yang ditulis server aplikasi ke storage/app/public.
            $foto = $kandidat->candidateProfile()->first()?->photo;
            if ($foto) {
                Storage::disk('public')->delete($foto);
            }
            @unlink($berkas);
        }
    }

    /** D02-07 (navigasi tab) */
    public function test_tab_navigasi_profil_dapat_dibuka(): void
    {
        $kandidat = $this->makeKandidat();

        $this->browse(function (Browser $browser) use ($kandidat) {
            $browser->loginAs($kandidat)
                ->visit(route('kandidat.profil.datadiri'))
                ->assertSee('Kelengkapan Profil')
                ->click('.tabs a:nth-child(2)')
                ->assertPathIs('/kandidat/profil');
        });
    }

    /** D02-08 (otorisasi: UMKM tidak boleh membuka halaman data diri kandidat) */
    public function test_umkm_tidak_dapat_membuka_halaman_data_diri_kandidat(): void
    {
        $umkm = $this->makeUmkm();

        $this->browse(function (Browser $browser) use ($umkm) {
            $browser->loginAs($umkm)
                ->visit(route('kandidat.profil.datadiri'))
                ->assertSee('403');
        });
    }
}
