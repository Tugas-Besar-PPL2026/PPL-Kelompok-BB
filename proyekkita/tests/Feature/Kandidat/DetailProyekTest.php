<?php

namespace Tests\Feature\Kandidat;

use App\Enums\ProjectStatus;
use App\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsApplicationScenario;
use Tests\TestCase;

/** [PBI-14 / FR-08] Daftar proyek terbuka dan halaman Detail Proyek, termasuk keadaan kartu lamaran (PBI-16). */
class DetailProyekTest extends TestCase
{
    use BuildsApplicationScenario;
    use RefreshDatabase;

    // ---------- PBI-14: daftar ----------

    public function test_daftar_hanya_menampilkan_proyek_dipublikasikan_yang_belum_lewat_tenggat(): void
    {
        $this->makeProject(['title' => 'Proyek Terbuka']);
        $this->makeProject(['title' => 'Proyek Draf', 'status' => ProjectStatus::Draft]);
        $this->makeProject(['title' => 'Proyek Ditutup', 'status' => ProjectStatus::Closed]);
        $this->makeProject(['title' => 'Proyek Kedaluwarsa', 'application_deadline' => now()->subDay()->toDateString()]);

        $this->actingAs($this->makeKandidat())
            ->get(route('kandidat.proyek.index'))
            ->assertOk()
            ->assertSee('Proyek Terbuka')
            ->assertDontSee('Proyek Draf')
            ->assertDontSee('Proyek Ditutup')
            ->assertDontSee('Proyek Kedaluwarsa');
    }

    public function test_daftar_menampilkan_data_dari_database_dan_pesan_saat_kosong(): void
    {
        $user = $this->makeKandidat();

        $this->actingAs($user)->get(route('kandidat.proyek.index'))->assertOk()->assertSee('Belum ada proyek yang terbuka');

        $this->makeProject(['title' => 'Logo Baru', 'total_compensation' => 3000000]);
        $this->get(route('kandidat.proyek.index'))
            ->assertSee('Logo Baru')->assertSee('Kopi Nusantara')->assertSee('Rp3.000.000')->assertSee('2 termin')
            ->assertSee('Lihat Detail');
    }

    public function test_halaman_hanya_untuk_kandidat(): void
    {
        $project = $this->makeProject();

        $this->actingAs($this->makeUmkm())->get(route('kandidat.proyek.index'))->assertForbidden();
        $this->actingAs($this->makeUmkm())->get(route('kandidat.proyek.show', $project))->assertForbidden();
    }

    // ---------- PBI-14: detail ----------

    public function test_detail_menampilkan_informasi_proyek_dan_umkm_dari_database(): void
    {
        $project = $this->makeProject(['title' => 'Judul Khusus Uji']);

        $this->actingAs($this->makeKandidat())
            ->get(route('kandidat.proyek.show', $project))
            ->assertOk()
            ->assertSee('Judul Khusus Uji')
            ->assertSee('Dibuka')
            ->assertSee('Desain Grafis &amp; Branding', false)
            ->assertSee('Desain label kemasan 3 SKU')       // tanda poin "•" dibuang dari data
            ->assertSee('File sumber Figma')
            ->assertSee('Rp2.500.000')
            ->assertSee('Termin 1')->assertSee('Uang muka setelah kesepakatan aktif')->assertSee('50%')
            ->assertSee('Pembayaran dilakukan langsung oleh UMKM di luar aplikasi')
            ->assertSee('Tentang UMKM')->assertSee('Kopi Nusantara')->assertSee('UMKM Terverifikasi')
            ->assertSee('Kuliner &amp; Minuman', false)->assertSee('Bandung')
            ->assertSee('Belum ada penilaian')
            ->assertSee('10 hari lagi');
    }

    public function test_detail_proyek_tidak_ditemukan_atau_belum_dipublikasikan_menghasilkan_404(): void
    {
        $user = $this->makeKandidat();

        $this->actingAs($user)->get(route('kandidat.proyek.show', 9999))->assertNotFound();
        foreach ([ProjectStatus::Draft, ProjectStatus::PendingReview, ProjectStatus::RevisionRequested, ProjectStatus::Rejected] as $status) {
            $this->get(route('kandidat.proyek.show', $this->makeProject(['status' => $status])))->assertNotFound();
        }
    }

    // ---------- PBI-16: keadaan kartu Ajukan Lamaran ----------

    public function test_kartu_lamaran_aktif_untuk_kandidat_yang_memenuhi_syarat(): void
    {
        $user = $this->makeKandidat(experiences: 2, portfolios: 3);
        $project = $this->makeProject();

        $this->actingAs($user)->get(route('kandidat.proyek.show', $project))
            ->assertOk()
            ->assertSee('Profil Anda memenuhi syarat melamar')
            ->assertSee('Proyek masih menerima lamaran')
            ->assertSee('Anda belum melamar proyek ini')
            ->assertSee('Dilampirkan otomatis dari profil')
            ->assertSee('1 keterampilan')->assertSee('2 pengalaman kerja')->assertSee('3 portofolio')
            ->assertSee('Wajib')->assertSee('Opsional')
            ->assertSee('dusk="kirim-lamaran"', false)
            ->assertDontSee('85%');

        $this->assertStringNotContainsString('disabled', $this->tombolKirim($user, $project));
    }

    public function test_kartu_lamaran_menampilkan_kondisi_sudah_melamar_dan_menonaktifkan_pengiriman(): void
    {
        $user = $this->makeKandidat();
        $project = $this->makeProject();
        $this->actingAs($user)->post(route('kandidat.proyek.lamaran.store', $project), ['message' => $this->validMessage()]);

        $this->get(route('kandidat.proyek.show', $project))
            ->assertSee('Lamaran Anda sudah terkirim')
            ->assertSee('Menunggu Keputusan')
            ->assertSee('Anda sudah melamar proyek ini')
            ->assertSee($this->validMessage());

        $this->assertStringContainsString('disabled', $this->tombolKirim($user, $project));
    }

    public function test_kartu_lamaran_nonaktif_saat_proyek_tidak_menerima_lamaran(): void
    {
        $user = $this->makeKandidat();
        $closed = $this->makeProject(['status' => ProjectStatus::Closed]);
        $expired = $this->makeProject(['application_deadline' => now()->subDays(2)->toDateString()]);

        $this->actingAs($user)->get(route('kandidat.proyek.show', $closed))
            ->assertOk()->assertSee('Ditutup')->assertSee('Pengajuan lamaran dinonaktifkan')
            ->assertSee('Proyek ini sudah ditutup dan tidak lagi menerima lamaran.');
        $this->assertStringContainsString('disabled', $this->tombolKirim($user, $closed));

        $this->get(route('kandidat.proyek.show', $expired))
            ->assertSee('Tenggat Berakhir')->assertSee('Tenggat lamaran proyek ini sudah berakhir.');
        $this->assertStringContainsString('disabled', $this->tombolKirim($user, $expired));
    }

    public function test_kartu_lamaran_nonaktif_dan_mengarahkan_ke_profil_jika_belum_memenuhi_syarat(): void
    {
        $user = $this->makeKandidat(skills: 0);
        $project = $this->makeProject();

        $this->actingAs($user)->get(route('kandidat.proyek.show', $project))
            ->assertOk()
            ->assertSee('Profil Anda belum memenuhi syarat melamar')
            ->assertSee('Lengkapi profil sebelum melamar')
            ->assertSee('tambahkan minimal 1 keterampilan');
        $this->assertStringContainsString('disabled', $this->tombolKirim($user, $project));
        $this->assertSame(0, Application::count());
    }

    public function test_pesan_validasi_tampil_di_halaman_setelah_pengiriman_gagal(): void
    {
        $user = $this->makeKandidat();
        $project = $this->makeProject();

        $this->actingAs($user)->from(route('kandidat.proyek.show', $project))
            ->post(route('kandidat.proyek.lamaran.store', $project), ['message' => 'terlalu pendek'])
            ->assertRedirect(route('kandidat.proyek.show', $project));

        $this->followingRedirects()->get(route('kandidat.proyek.show', $project))
            ->assertSee('Pesan lamaran minimal 50 karakter.');
    }

    private function tombolKirim($user, $project): string
    {
        $html = $this->actingAs($user)->get(route('kandidat.proyek.show', $project))->getContent();
        preg_match('/<button[^>]*dusk="kirim-lamaran"[^>]*>/', $html, $m);

        return $m[0] ?? '';
    }
}
