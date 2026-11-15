<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\Concerns\BuildsApplicationScenario;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

/** [PBI-01 / FR-02] Profil usaha melalui browser. Database: lihat UsesDuskTestDatabase. */
class ProfilUsahaTest extends DuskTestCase
{
    use BuildsApplicationScenario, UsesDuskTestDatabase;

    public function test_umkm_mengubah_nama_usaha_dan_status_kembali_menunggu(): void
    {
        $umkm = $this->makeUmkm(['nib' => '9120304567812']);

        $this->browse(function (Browser $browser) use ($umkm) {
            $browser->loginAs($umkm)
                ->visit(route('umkm.profil.edit'))
                ->assertSeeIn('@status-verifikasi', 'Terverifikasi')
                ->type('@nama-usaha', 'Kopi Nusantara Baru')
                ->press('@simpan-profil')
                ->waitForText('Profil usaha berhasil disimpan.')
                ->assertSeeIn('@status-verifikasi', 'Menunggu Pemeriksaan');
        });
    }

    public function test_nama_usaha_kosong_menampilkan_pesan_validasi(): void
    {
        $umkm = $this->makeUmkm(['nib' => '9120304567812']);

        $this->browse(function (Browser $browser) use ($umkm) {
            $browser->loginAs($umkm)
                ->visit(route('umkm.profil.edit'))
                ->clear('@nama-usaha')
                ->press('@simpan-profil')
                ->waitFor('@galat-nama-usaha')
                ->assertSeeIn('@galat-nama-usaha', 'Nama usaha wajib diisi.');
        });
    }

    public function test_nib_dan_tautan_kosong_menampilkan_pesan_validasi(): void
    {
        $umkm = $this->makeUmkm(['nib' => '9120304567812']);

        $this->browse(function (Browser $browser) use ($umkm) {
            $browser->loginAs($umkm)
                ->visit(route('umkm.profil.edit'))
                ->clear('@nib')
                ->clear('@tautan-usaha')
                ->press('@simpan-profil')
                ->waitFor('@galat-nib')
                ->assertSeeIn('@galat-nib', 'Isi NIB atau tautan informasi usaha.');
        });
    }
}