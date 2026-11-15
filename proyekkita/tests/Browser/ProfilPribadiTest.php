<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\Concerns\BuildsApplicationScenario;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

/** [PBI-02 / FR-03] Profil pribadi dan akademik melalui browser. */
class ProfilPribadiTest extends DuskTestCase
{
    use BuildsApplicationScenario, UsesDuskTestDatabase;

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
}
