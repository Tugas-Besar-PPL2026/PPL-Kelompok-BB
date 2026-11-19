<?php

namespace Tests\Browser\PBI43;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

/**
 * TC-PBI43-022 (sheet PBI-43, Test_Case_Kelompok_BB_SI4801_1.xlsx).
 *
 * E2E Laravel Dusk: registrasi, login, dan logout dilakukan lewat UI Chrome. Tanpa loginAs/actingAs,
 * tanpa manipulasi session/cookie untuk melewati login, tanpa factory/seeder/SQL tulis.
 * Akun Kandidat/UMKM dibuat lewat halaman registrasi dengan email unik per run.
 * Status login diverifikasi lewat perilaku aplikasi: /login khusus tamu (pengguna login dialihkan ke halaman
 * awalnya), halaman terproteksi mengalihkan tamu ke /login. Database hanya dibaca untuk verifikasi tambahan.
 */
#[Group('e2e-auth')]
#[Group('pbi43')]
#[Group('TC-PBI43-022')]
class TC_PBI43_022_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use UsesDuskTestDatabase;

    /**
     * TC-PBI43-022 - Halaman registrasi untuk peran Admin tidak tersedia.
     */
    public function test_TC_PBI43_022_registrasi_admin_tidak_tersedia(): void
    {
        $this->browse(function (Browser $browser) {
            $jumlahUserSebelum = DB::table('users')->count();

            // Langkah 1-2: buka /daftar/admin secara langsung; halaman 404 dan form tidak tampil.
            $browser->visit('/daftar/admin')
                ->assertSourceHas('Not Found')
                ->assertMissing('@daftar')
                ->assertMissing('input[name=password]');

            $this->assertSame($jumlahUserSebelum, DB::table('users')->count(), 'Tidak boleh ada akun baru.');
        });
    }
}
