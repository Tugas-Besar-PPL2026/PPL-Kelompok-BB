<?php

namespace Tests\Browser\PBI08;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TC_PBI8_029_Test extends DuskTestCase
{
    public function test_alur_utuh_pbi1_pbi8_lalu_pbi1(): void
    {
        $this->browse(function (Browser $browser) {
            $umkmBaru = User::factory()->create(['role' => 'umkm']);
            $admin = User::where('email', 'admin@proyekkita.test')->firstOrFail();

            // 1. UMKM mengisi profil lalu simpan -> Menunggu Pemeriksaan
            $browser->loginAs($umkmBaru)
                ->visit('/umkm/profil')
                ->type('business_name', 'Usaha Alur Utuh')
                ->type('business_field', 'Kuliner')
                ->type('description', 'Deskripsi usaha alur utuh')
                ->type('phone', '081111111111')
                ->type('city', 'Bandung')
                ->type('address', 'Jl. Merdeka No. 1')
                ->type('nib', '9120304567899')
                ->press('Simpan Perubahan')
                ->assertSee('Menunggu Pemeriksaan');

            // 2. Admin menyetujui -> Terverifikasi
            $browser->loginAs($admin)
                ->visit('/admin/verifikasi')
                ->press('Setujui')
                ->assertSee('Akun UMKM berhasil diverifikasi.');

            // 3. UMKM mengubah kontak (tetap Terverifikasi), lalu mengubah nama usaha (jadi Menunggu Pemeriksaan)
            $browser->loginAs($umkmBaru)
                ->visit('/umkm/profil')
                ->type('phone', '082222222222')
                ->press('Simpan Perubahan')
                ->assertSee('Terverifikasi')
                ->type('business_name', 'Usaha Alur Utuh Revisi')
                ->press('Simpan Perubahan')
                ->assertSee('Menunggu Pemeriksaan');

            // 4. Admin menolak dengan alasan -> Ditolak
            $browser->loginAs($admin)
                ->visit('/admin/verifikasi')
                ->type('rejection_reason', 'Perlu penyesuaian data usaha.')
                ->press('Tolak')
                ->assertSee('Verifikasi UMKM ditolak.');

            // 5. UMKM memperbaiki profil dan menyimpan -> kembali Menunggu Pemeriksaan
            $browser->loginAs($umkmBaru)
                ->visit('/umkm/profil')
                ->type('business_name', 'Usaha Alur Utuh Final')
                ->press('Simpan Perubahan')
                ->assertSee('Menunggu Pemeriksaan');
        });
    }
}