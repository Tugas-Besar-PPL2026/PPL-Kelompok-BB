<?php

namespace Tests\Feature\Kandidat;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsApplicationScenario;
use Tests\TestCase;

/** [PBI-02 / FR-03] Profil pribadi dan akademik kandidat. */
class ProfilPribadiTest extends TestCase
{
    use RefreshDatabase, BuildsApplicationScenario;

    private function payload(array $o = []): array
    {
        return array_merge([
            'full_name' => 'Fajar Pratama',
            'city' => 'Kota Bandung',
            'headline' => 'Desainer grafis & kemasan produk UMKM',
            'university' => 'Telkom University',
            'major' => 'S1 Desain Komunikasi Visual',
            'summary' => 'Mahasiswa DKV semester 7 yang berfokus pada desain kemasan.',
        ], $o);
    }

    public function test_kandidat_dapat_membuka_halaman_data_diri(): void
    {
        $this->actingAs($this->makeKandidat())->get(route('kandidat.profil.datadiri'))
            ->assertOk()
            ->assertSee('Data Pribadi')
            ->assertSee('Kelengkapan Profil');
    }

    public function test_umkm_tidak_dapat_membuka_data_diri_kandidat(): void
    {
        $this->actingAs($this->makeUmkm())->get(route('kandidat.profil.datadiri'))->assertForbidden();
    }

    public function test_kandidat_baru_tanpa_profil_dapat_membuat_profil(): void
    {
        $user = User::factory()->kandidat()->create();

        $this->actingAs($user)->put(route('kandidat.profil.datadiri.update'), $this->payload())
            ->assertRedirect(route('kandidat.profil.datadiri'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('candidate_profiles', ['user_id' => $user->id, 'full_name' => 'Fajar Pratama']);
    }

    public function test_kandidat_dapat_memperbarui_profil(): void
    {
        $user = $this->makeKandidat();

        $this->actingAs($user)->put(route('kandidat.profil.datadiri.update'), $this->payload(['university' => 'ITB']));

        $this->assertDatabaseHas('candidate_profiles', ['user_id' => $user->id, 'university' => 'ITB']);
    }

    public function test_semua_isian_wajib(): void
    {
        $user = $this->makeKandidat();

        $this->actingAs($user)->put(route('kandidat.profil.datadiri.update'), [])
            ->assertSessionHasErrors(['full_name', 'city', 'headline', 'university', 'major', 'summary']);
    }

    public function test_headline_maksimal_80_karakter(): void
    {
        $user = $this->makeKandidat();

        $this->actingAs($user)
            ->put(route('kandidat.profil.datadiri.update'), $this->payload(['headline' => str_repeat('a', 81)]))
            ->assertSessionHasErrors('headline');
    }

    public function test_foto_valid_tersimpan_dan_foto_lama_diganti(): void
    {
        Storage::fake('public');
        $user = $this->makeKandidat();

        $this->actingAs($user)->put(route('kandidat.profil.datadiri.update'),
            $this->payload(['photo' => UploadedFile::fake()->image('a.jpg')]));
        $first = $user->candidateProfile()->first()->photo;
        Storage::disk('public')->assertExists($first);

        $this->actingAs($user)->put(route('kandidat.profil.datadiri.update'),
            $this->payload(['photo' => UploadedFile::fake()->image('b.png')]));
        $second = $user->candidateProfile()->first()->photo;

        Storage::disk('public')->assertExists($second);
        Storage::disk('public')->assertMissing($first);
    }

    public function test_foto_lebih_dari_2mb_ditolak(): void
    {
        Storage::fake('public');
        $user = $this->makeKandidat();

        $this->actingAs($user)->put(route('kandidat.profil.datadiri.update'),
            $this->payload(['photo' => UploadedFile::fake()->image('besar.jpg')->size(3000)]))
            ->assertSessionHasErrors('photo');
    }

    public function test_foto_bukan_gambar_ditolak(): void
    {
        Storage::fake('public');
        $user = $this->makeKandidat();

        $this->actingAs($user)->put(route('kandidat.profil.datadiri.update'),
            $this->payload(['photo' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf')]))
            ->assertSessionHasErrors('photo');
    }
}
