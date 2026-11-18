<?php

namespace Tests\Feature\Kandidat;

use Tests\DuskTestCase;
use Tests\Concerns\AlurProyekKita;
use Laravel\Dusk\Browser;

class EksplorProyekTest extends DuskTestCase
{
    use AlurProyekKita;

    // TC-001: Pencarian proyek berdasarkan judul
    public function test_pencarian_proyek_berdasarkan_judul(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginKandidat($browser);

            $this->terapkanFilter($browser, [
                'cari' => 'Redesain Logo Baju',
            ]);

            $browser->assertSee('Redesain Logo Baju')
                ->assertDontSee('Redesain Katalog Digital & Label Kemasan')
                ->screenshot('eksplor-tc001-pencarian');
        });
    }

    // TC-002: Filter proyek berdasarkan kategori
    public function test_filter_proyek_berdasarkan_kategori(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginKandidat($browser);

            $this->terapkanFilter($browser, [
                'kategori' => 83,
            ]);

            $browser->assertSee('Redesain Logo Baju')
                ->assertSee('Redesain Katalog Digital & Label Kemasan')
                ->screenshot('eksplor-tc002-kategori');
        });
    }

    // TC-003: Filter proyek berdasarkan kompensasi
    public function test_filter_proyek_berdasarkan_kompensasi(): void
    {
    $this->browse(function (Browser $browser) {
        $this->loginKandidat($browser);

        $this->terapkanFilter($browser, [
            'kompensasi_min' => 2000000,
            'kompensasi_maks' => 3000000,
        ]);

        $browser->assertSee('Redesain Katalog Digital & Label Kemasan')
            ->assertDontSee('Redesain Logo Baju')
            ->screenshot('eksplor-tc003-kompensasi');
        });
    }

    // TC-004: Filter proyek berdasarkan tenggat
    public function test_filter_proyek_berdasarkan_tenggat(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginKandidat($browser);

            $this->terapkanFilter($browser, [
                'tenggat_sebelum' => '2026-11-30',
            ]);

            $browser->assertSee('Redesain Logo Baju')
                ->assertDontSee('Redesain Katalog Digital & Label Kemasan')
                ->screenshot('eksplor-tc004-tenggat');
        });
    }

    
    // TC-005: Mengurutkan proyek berdasarkan kompensasi tertinggi
    public function test_urutkan_proyek_berdasarkan_kompensasi_tertinggi(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginKandidat($browser);

            $this->terapkanFilter($browser, [
                'urut' => 'kompensasi_tertinggi',
            ]);

            $browser->assertSee('Redesain Katalog Digital & Label Kemasan')
                ->assertSee('Redesain Logo Baju');

            $nilaiUrutan = $browser->script("
                return document.querySelector('[dusk=\"urutkan\"]')?.value;
            ");

            $this->assertEquals('kompensasi_tertinggi', $nilaiUrutan[0]);

            $browser->screenshot('eksplor-tc005-pengurutan');
        });
    }
}
