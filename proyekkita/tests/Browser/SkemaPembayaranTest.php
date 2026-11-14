<?php

namespace Tests\Browser;

use App\Models\Category;
use App\Models\Project;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\Concerns\BuildsApplicationScenario;
use Tests\DuskTestCase;

class SkemaPembayaranTest extends DuskTestCase
{
    use BuildsApplicationScenario;

    public function test_umkm_menyimpan_draf_dengan_dua_termin_melalui_browser(): void
    {
        $umkm = $this->makeUmkm();
        $judul = 'Uji Skema Pembayaran ' . Str::random(6);

        $this->browse(function (Browser $browser) use ($umkm, $judul) {
            $this->isiFormDenganDuaTermin($browser, $umkm, $judul, 1250000, 1250000)
                ->assertSeeIn('@status-termin', 'Jumlah termin sesuai total kompensasi');

            $this->simpanDraf($browser)->waitForText('Draf proyek disimpan.', 15);
        });

        $project = Project::where('title', $judul)->firstOrFail();
        $this->assertEquals([1, 2], $project->paymentTerms()->pluck('term_number')->all());
        $this->assertEquals(2500000, (float) $project->paymentTerms()->sum('amount'));
    }

    public function test_draf_ditolak_jika_jumlah_termin_tidak_sama_dengan_total(): void
    {
        $umkm = $this->makeUmkm();
        $judul = 'Uji Skema Pembayaran ' . Str::random(6);

        $this->browse(function (Browser $browser) use ($umkm, $judul) {
            $this->isiFormDenganDuaTermin($browser, $umkm, $judul, 1000000, 1000000)
                ->assertSeeIn('@status-termin', 'Jumlah termin belum sesuai total kompensasi');

            $this->simpanDraf($browser)->waitForText('Jumlah nominal termin harus sama dengan total kompensasi.');
        });

        $this->assertFalse(Project::where('title', $judul)->exists());
    }

    /** Tombol simpan ada di atas halaman, jadi halaman di-scroll ke atas dulu agar tidak tertutup navbar. */
    private function simpanDraf(Browser $browser): Browser
    {
        $browser->script('window.scrollTo(0, 0);');

        return $browser->press('@simpan-draf');
    }

    /** Mengisi form Buat Proyek dengan total kompensasi 2.500.000 dan dua termin bertahap. */
    private function isiFormDenganDuaTermin(Browser $browser, User $umkm, string $judul, int $termin1, int $termin2): Browser
    {
        $category = Category::firstOrCreate(['name' => 'Desain Grafis & Branding']);
        $skill = Skill::firstOrCreate(['name' => 'Figma']);
        $tenggat = now()->addDays(7)->toDateString();
        $target = now()->addDays(30)->toDateString();

        $browser->loginAs($umkm)
            ->visit(route('umkm.proyek.create'))
            ->type('title', $judul)
            ->select('category_id', $category->id)
            ->click('@skill-' . $skill->id)
            ->type('description', 'Memperbarui label kemasan tiga varian biji kopi.')
            ->type('expected_output', 'Desain label kemasan 3 SKU siap cetak');

        // Input tanggal diisi lewat JavaScript karena format ketik tanggal berbeda-beda per pengaturan bahasa browser.
        $browser->script([
            "document.getElementById('application_deadline').value = '{$tenggat}';",
            "document.getElementById('target_completion_date').value = '{$target}';",
        ]);

        return $browser->type('quota', '2')
            ->type('total_compensation', '2500000')
            ->radio('payment_method', 'bertahap')
            ->type('#tabel-termin tbody tr:nth-child(1) .term-amount', (string) $termin1)
            ->type('#tabel-termin tbody tr:nth-child(1) [name$="[condition]"]', 'Uang muka setelah kesepakatan berstatus aktif')
            ->type('#tabel-termin tbody tr:nth-child(2) .term-amount', (string) $termin2)
            ->type('#tabel-termin tbody tr:nth-child(2) [name$="[condition]"]', 'Pelunasan setelah hasil akhir disetujui UMKM');
    }
}
