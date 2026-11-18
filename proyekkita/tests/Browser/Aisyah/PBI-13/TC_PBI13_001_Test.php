<?php

namespace Tests\Browser\Aisyah\PBI13;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EPbi131415;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

/**
 * TC-PBI13-001 (sheet PBI 13, PBI 13 14 15.xlsx).
 *
 * Alur E2E (semua lewat Chrome, tanpa loginAs/factory/seeder/SQL):
 *   1. Registrasi UMKM baru lewat /daftar/umkm (langsung masuk).
 *   2. Menu Profil Usaha, isi profil Uniqlo, Simpan Perubahan.
 *   3. Menu Proyek Saya, periksa keadaan kosong.
 * Database MySQL test persistent hanya dibaca untuk verifikasi tambahan.
 */
#[Group('e2e-proyek')]
#[Group('pbi13')]
#[Group('TC-PBI13-001')]
class TC_PBI13_001_Test extends DuskTestCase
{
    use AlurE2EPbi131415;
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use UsesDuskTestDatabase;

    /**
     * TC-PBI13-001 - UMKM baru yang belum punya proyek membuka Proyek Saya dan melihat keadaan kosong.
     */
    public function test_TC_PBI13_001_proyek_saya_kosong_untuk_umkm_baru(): void
    {
        $this->browse(function (Browser $browser) {
            // Langkah 1: daftar lewat halaman registrasi sebagai UMKM (setelah registrasi pengguna langsung masuk).
            $akun = $this->daftarUmkmBaru($browser, 'PBI13');

            // Langkah 2: menu Profil Usaha, isi seluruh kolom, Simpan Perubahan.
            $this->klikTautan($browser, 'Profil Usaha')->assertPathIs('/umkm/profil');
            $this->isiProfilUsaha($browser, $this->profilUniqlo())
                ->assertSeeIn('@status-verifikasi', 'Menunggu Pemeriksaan');

            // Langkah 3: menu Proyek Saya.
            $this->klikTautan($browser, 'Proyek Saya')
                ->assertPathIs('/umkm/proyek')
                ->assertSee('Proyek Saya')
                ->assertVisible('@proyek-kosong')
                ->assertSeeIn('@proyek-kosong', 'Belum ada proyek')
                ->assertSeeIn('@proyek-kosong', 'Buat Proyek')
                ->assertMissing('@tab-status-proyek');

            $this->assertSame(
                ['dipublikasikan' => 0, 'menunggu' => 0, 'perbaikan' => 0, 'pelamar' => 0],
                $this->kartuRingkasan($browser),
                'Keempat kartu ringkasan harus bernilai 0.'
            );
            $this->assertSame([], $this->idKartuProyekSaya($browser));

            // Verifikasi tambahan di database (hanya membaca).
            $this->assertSame(0, $this->jumlahProyekUmkmDb($akun['email']), 'Tabel projects tidak memiliki baris milik UMKM ini.');
        });
    }
}
