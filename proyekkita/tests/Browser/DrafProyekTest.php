<?php

namespace Tests\Browser;

use App\Enums\ProjectStatus;
use App\Models\Category;
use App\Models\Project;
use App\Models\Skill;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\Concerns\BuildsApplicationScenario;
use Tests\DuskTestCase;

/**
 * PBI-9 - Membuat dan memperbarui draf proyek melalui browser: pengisian form, pesan error di layar,
 */
class DrafProyekTest extends DuskTestCase
{
    use BuildsApplicationScenario;

    public function test_umkm_membuat_draf_proyek_baru(): void
    {
        $umkm = $this->makeUmkm();
        $judul = 'Uji Draf Proyek ' . Str::random(6);

        $this->browse(function (Browser $browser) use ($umkm, $judul) {
            $this->isiFormBuatProyek($browser->loginAs($umkm), $judul);

            $this->simpan($browser, 'Simpan sebagai Draf')
                ->waitForText('Draf proyek disimpan.', 15)
                ->assertPathEndsWith('/ubah')
                ->assertInputValue('title', $judul);
        });

        $project = Project::where('title', $judul)->firstOrFail();
        $this->assertSame(ProjectStatus::Draft, $project->status);
        $this->assertSame($umkm->umkmProfile->id, $project->umkm_profile_id);
        $this->assertSame(1, $project->skills()->count());
    }

       public function test_kolom_wajib_kosong_menampilkan_pesan_error(): void
    {
        $umkm = $this->makeUmkm();
        $jumlahAwal = Project::count();

        $this->browse(function (Browser $browser) use ($umkm) {
            $browser->loginAs($umkm)->visit(route('umkm.proyek.create'));

            $this->simpan($browser, 'Simpan sebagai Draf')
                ->waitForText('Judul proyek wajib diisi.', 15)
                ->assertSee('Kategori wajib diisi.')
                ->assertSee('Keterampilan wajib diisi.')
                ->assertSee('Tenggat lamaran wajib diisi.')
                ->assertPathIs('/umkm/proyek/buat');
        });

        $this->assertSame($jumlahAwal, Project::count());
    }

    public function test_umkm_mengubah_draf_miliknya(): void
    {
        $umkm = $this->makeUmkm();
        $project = $this->makeProject(['status' => ProjectStatus::Draft, 'published_at' => null], $umkm->umkmProfile);

        $this->browse(function (Browser $browser) use ($umkm, $project) {
            $browser->loginAs($umkm)
                ->visit(route('umkm.proyek.edit', $project))
                ->assertInputValue('title', $project->title)
                ->type('title', 'Judul Draf Sudah Diubah')
                ->type('quota', '3');

            $this->simpan($browser, 'Simpan Perubahan')
                ->waitForText('Draf proyek diperbarui.', 15)
                ->assertInputValue('title', 'Judul Draf Sudah Diubah');
        });

        $project->refresh();
        $this->assertSame('Judul Draf Sudah Diubah', $project->title);
        $this->assertSame(3, $project->quota);
        $this->assertSame(ProjectStatus::Draft, $project->status);
    }

    public function test_proyek_yang_sudah_tayang_tidak_bisa_diubah_sebagai_draf(): void
    {
        $umkm = $this->makeUmkm();
        $project = $this->makeProject([], $umkm->umkmProfile); // bawaan makeProject: status Published

        $this->browse(function (Browser $browser) use ($umkm, $project) {
            $browser->loginAs($umkm)
                ->visit(route('umkm.proyek.edit', $project))
                ->assertSee('403')
                ->assertDontSee('Simpan Perubahan');
        });
    }

    public function test_umkm_lain_tidak_bisa_membuka_draf_milik_orang_lain(): void
    {
        $pemilik = $this->makeUmkm();
        $project = $this->makeProject(['status' => ProjectStatus::Draft, 'published_at' => null], $pemilik->umkmProfile);
        $umkmLain = $this->makeUmkm();

        $this->browse(function (Browser $browser) use ($umkmLain, $project) {
            $browser->loginAs($umkmLain)
                ->visit(route('umkm.proyek.edit', $project))
                ->assertSee('403')
                ->assertDontSee($project->title);
        });
    }

    /** Mengisi semua kolom wajib pada halaman Buat Proyek dengan data yang valid. */
    private function isiFormBuatProyek(Browser $browser, string $judul): Browser
    {
        $category = Category::firstOrCreate(['name' => 'Desain Grafis & Branding']);
        $skill = Skill::firstOrCreate(['name' => 'Figma']);
        $tenggat = now()->addDays(7)->toDateString();
        $target = now()->addDays(30)->toDateString();

        $browser->visit(route('umkm.proyek.create'))
            ->type('title', $judul)
            ->select('category_id', $category->id)
            ->clickAtXPath("//label[input[@name='skills[]' and @value='{$skill->id}']]")
            ->type('description', 'Memperbarui label kemasan tiga varian biji kopi.')
            ->type('expected_output', 'Desain label kemasan 3 SKU siap cetak');

        $browser->script([
            "document.getElementById('application_deadline').value = '{$tenggat}';",
            "document.getElementById('target_completion_date').value = '{$target}';",
        ]);

        $browser->type('quota', '2')->type('total_compensation', '2500000');

        // Bagian skema pembayaran (PBI-10) wajib diisi jika sudah ada di form.
        if ($browser->element('[name="payment_method"]')) {
            $browser->radio('payment_method', 'sekali')
                ->type('#tabel-termin tbody tr:nth-child(1) [name$="[condition]"]', 'Dibayar setelah hasil akhir disetujui UMKM');
        }

        return $browser;
    }

    /** Navbar menempel di atas halaman. */
    private function simpan(Browser $browser, string $tombol): Browser
    {
        $browser->script('window.scrollTo(0, 0);');

        return $browser->press($tombol);
    }
}
