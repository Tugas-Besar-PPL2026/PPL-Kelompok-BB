<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CandidatePortfolioTest extends TestCase
{
    use RefreshDatabase;

    private function createKandidatWithProfile(string $name = 'Fajar Pratama'): User
    {
        $user = User::factory()->create(['role' => UserRole::Kandidat]);
        $user->candidateProfile()->create([
            'full_name' => $name,
            'headline' => 'Desainer grafis & kemasan produk UMKM',
            'university' => 'Telkom University',
            'major' => 'S1 Desain Komunikasi Visual',
            'city' => 'Kota Bandung',
            'summary' => 'Mahasiswa DKV yang berfokus pada desain kemasan.',
        ]);

        return $user;
    }

    public function test_kandidat_dapat_membuka_halaman_portofolio(): void
    {
        $user = $this->createKandidatWithProfile();
        $portfolio = $user->candidateProfile->portfolios()->create([
            'title' => 'Label Kemasan Sambal Bu Iyah',
            'description' => 'Redesain label 3 varian sambal kemasan botol, siap cetak.',
            'link' => 'https://behance.net/fajarpratama/sambal-bu-iyah',
        ]);

        $response = $this->actingAs($user)->get(route('kandidat.profil.portofolio'));

        $response->assertOk();
        $response->assertSee('Tambah Portofolio');
        $response->assertSee($portfolio->title);
        $response->assertSee('behance.net/fajarpratama/sambal-bu-iyah');
    }

    public function test_kandidat_dapat_menambahkan_portofolio_dengan_tautan(): void
    {
        $user = $this->createKandidatWithProfile();

        $response = $this->actingAs($user)->post(route('kandidat.profil.portofolio.store'), [
            'title' => 'Identitas Visual Kedai Seduh',
            'description' => 'Logo, kartu menu, dan stiker gelas.',
            'link' => 'dribbble.com/fajarp/kedai-seduh',
        ]);

        $response->assertRedirect(route('kandidat.profil.portofolio'));
        $this->assertDatabaseHas('portfolios', [
            'candidate_profile_id' => $user->candidateProfile->id,
            'title' => 'Identitas Visual Kedai Seduh',
            'link' => 'https://dribbble.com/fajarp/kedai-seduh',
        ]);
    }

    public function test_kandidat_dapat_menambahkan_portofolio_dengan_dokumen(): void
    {
        Storage::fake('public');
        $user = $this->createKandidatWithProfile();
        $file = UploadedFile::fake()->create('katalog-roti-kampung.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($user)->post(route('kandidat.profil.portofolio.store'), [
            'title' => 'Katalog Produk Roti Kampung',
            'description' => 'Katalog digital 12 halaman untuk reseller.',
            'file' => $file,
        ]);

        $response->assertRedirect(route('kandidat.profil.portofolio'));
        $portfolio = $user->candidateProfile->portfolios()->first();
        $this->assertNotNull($portfolio);
        $this->assertNotNull($portfolio->file_path);
        Storage::disk('public')->assertExists($portfolio->file_path);
    }

    public function test_validasi_gagal_bila_tautan_dan_dokumen_keduanya_kosong(): void
    {
        $user = $this->createKandidatWithProfile();

        $response = $this->actingAs($user)->post(route('kandidat.profil.portofolio.store'), [
            'title' => 'Portofolio Tanpa Bukti',
            'description' => 'Deskripsi singkat.',
            'link' => '',
        ]);

        $response->assertSessionHasErrors('link');
        $this->assertDatabaseCount('portfolios', 0);
    }

    public function test_kandidat_dapat_memperbarui_portofolio(): void
    {
        $user = $this->createKandidatWithProfile();
        $portfolio = $user->candidateProfile->portfolios()->create([
            'title' => 'Judul Awal',
            'description' => 'Deskripsi awal.',
            'link' => 'https://behance.net/awal',
        ]);

        $response = $this->actingAs($user)->put(route('kandidat.profil.portofolio.update', $portfolio), [
            'title' => 'Judul Diperbarui',
            'description' => 'Deskripsi diperbarui.',
            'link' => 'https://behance.net/baru',
        ]);

        $response->assertRedirect(route('kandidat.profil.portofolio'));
        $this->assertDatabaseHas('portfolios', [
            'id' => $portfolio->id,
            'title' => 'Judul Diperbarui',
            'link' => 'https://behance.net/baru',
        ]);
    }

    public function test_kandidat_dapat_menghapus_portofolio(): void
    {
        Storage::fake('public');
        $user = $this->createKandidatWithProfile();
        $path = UploadedFile::fake()->create('sampel.pdf', 200, 'application/pdf')
            ->storeAs('portfolios/' . $user->candidateProfile->id, 'sampel.pdf', 'public');

        $portfolio = $user->candidateProfile->portfolios()->create([
            'title' => 'Portofolio Dihapus',
            'description' => 'Akan dihapus.',
            'file_path' => $path,
        ]);

        $response = $this->actingAs($user)->delete(route('kandidat.profil.portofolio.destroy', $portfolio));

        $response->assertRedirect(route('kandidat.profil.portofolio'));
        $this->assertDatabaseMissing('portfolios', ['id' => $portfolio->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_kandidat_tidak_dapat_mengubah_atau_menghapus_portofolio_kandidat_lain(): void
    {
        $owner = $this->createKandidatWithProfile('Pemilik');
        $other = $this->createKandidatWithProfile('Kandidat Lain');

        $portfolio = $owner->candidateProfile->portfolios()->create([
            'title' => 'Portofolio Milik Pemilik',
            'description' => 'Deskripsi.',
            'link' => 'https://behance.net/pemilik',
        ]);

        $this->actingAs($other)
            ->put(route('kandidat.profil.portofolio.update', $portfolio), [
                'title' => 'Diubah',
                'link' => 'https://behance.net/diubah',
            ])
            ->assertForbidden();

        $this->actingAs($other)
            ->delete(route('kandidat.profil.portofolio.destroy', $portfolio))
            ->assertForbidden();
    }
}
