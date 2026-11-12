<?php

namespace Tests\Browser;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\Concerns\BuildsCancellationScenario;
use Tests\DuskTestCase;

/**
 * [PBI-18 / FR-10] Alur pembatalan lamaran melalui browser: tombol, dialog konfirmasi, dan hasil pembatalan.
 *
 * Pelengkap Feature Test (tests/Feature/Kandidat/BatalkanLamaranTest.php), bukan penggantinya.
 * Mengikuti pola Dusk PBI-16: data dibuat unik per test dan asersi database dibatasi pada lamaran milik test ini.
 *
 * Selector memakai atribut dusk pada partial kandidat/lamaran/_batalkan.blade.php:
 *   batalkan-lamaran-{id}, dialog-batal-{id}, tutup-batal-{id}, konfirmasi-batal-{id}
 */
class BatalkanLamaranTest extends DuskTestCase
{
    use BuildsCancellationScenario;

    private function judul(string $awalan): string
    {
        return $awalan.' '.Str::random(6);
    }

    // =====================================================================
    // Alur positif
    // =====================================================================

    public function test_kandidat_membatalkan_lamaran_menunggu_keputusan_lewat_dialog_konfirmasi(): void
    {
        $kandidat = $this->makeKandidat();
        $judul = $this->judul('Dusk Batal Menunggu');
        $lamaran = $this->makeApplicationFor($kandidat, ApplicationStatus::Pending, ['title' => $judul]);
        $id = $lamaran->id;

        $this->browse(function (Browser $browser) use ($kandidat, $judul, $id) {
            $browser->loginAs($kandidat)
                ->visit(route('kandidat.lamaran.index'))
                ->assertSee($judul)
                ->assertPresent("@batalkan-lamaran-{$id}")
                ->assertMissing("@dialog-batal-{$id}")          // dialog belum tampil

                // 1) Klik tombol: dialog konfirmasi muncul dan menjelaskan akibatnya.
                ->click("@batalkan-lamaran-{$id}")
                ->waitFor("@dialog-batal-{$id}")
                ->assertSeeIn("@dialog-batal-{$id}", 'Batalkan lamaran?')
                ->assertSeeIn("@dialog-batal-{$id}", $judul)
                ->assertSeeIn("@dialog-batal-{$id}", 'Dibatalkan')
                ->assertSeeIn("@dialog-batal-{$id}", 'tidak dapat mengajukan lamaran kembali')

                // 2) Konfirmasi: lamaran dibatalkan dan kandidat kembali ke Lamaran Saya.
                ->press("@konfirmasi-batal-{$id}")
                ->waitForText('Lamaran berhasil dibatalkan')
                ->assertSee('Lamaran berhasil dibatalkan dan berstatus Dibatalkan.')
                ->assertMissing("@batalkan-lamaran-{$id}")       // tombol tidak lagi tersedia
                ->assertSeeIn("#lamaran-{$id}", 'Dibatalkan')
                ->assertSeeIn("#lamaran-{$id}", 'Dibatalkan oleh Anda pada')
                ->assertSeeIn("#lamaran-{$id}", 'Riwayat status (2)');
        });

        // Status berubah, data lamaran tetap ada, dan riwayat pembatalan tersimpan.
        $lamaran = Application::findOrFail($id);
        $this->assertSame(ApplicationStatus::Cancelled, $lamaran->status);
        $this->assertSame(2, ApplicationStatusHistory::where('application_id', $id)->count());
        $this->assertDatabaseHas('application_status_histories', [
            'application_id' => $id, 'status' => 'cancelled', 'note' => 'Dibatalkan oleh kandidat.',
        ]);
    }

    public function test_kandidat_membatalkan_lamaran_terpilih_sementara_dan_tab_filter_dipertahankan(): void
    {
        $kandidat = $this->makeKandidat();
        $lamaran = $this->makeApplicationFor($kandidat, ApplicationStatus::Shortlisted, [
            'title' => $this->judul('Dusk Batal Terpilih'),
            'selection_note' => 'Anda masuk daftar pendek.',
            'histories' => [[ApplicationStatus::Shortlisted, '2026-11-06 11:30:00', 'Anda masuk daftar pendek.']],
        ]);
        $id = $lamaran->id;

        $this->browse(function (Browser $browser) use ($kandidat, $id) {
            $browser->loginAs($kandidat)
                ->visit(route('kandidat.lamaran.index', ['status' => 'shortlisted']))
                ->assertPresent("@batalkan-lamaran-{$id}")
                ->click("@batalkan-lamaran-{$id}")
                ->waitFor("@dialog-batal-{$id}")
                ->press("@konfirmasi-batal-{$id}")
                ->waitForText('Lamaran berhasil dibatalkan')

                // Kembali ke tab yang sama; lamaran sudah berpindah ke tab Dibatalkan.
                ->assertQueryStringHas('status', 'shortlisted')
                ->assertSee('Tidak ada lamaran dengan status Terpilih Sementara')
                ->visit(route('kandidat.lamaran.index', ['status' => 'cancelled']))
                ->assertSeeIn("#lamaran-{$id}", 'Dibatalkan')
                ->assertSeeIn("#lamaran-{$id}", 'Anda masuk daftar pendek.'); // catatan UMKM tidak hilang
        });

        $lamaran = Application::findOrFail($id);
        $this->assertSame(ApplicationStatus::Cancelled, $lamaran->status);
        $this->assertSame('Anda masuk daftar pendek.', $lamaran->selection_note);
        $this->assertSame(3, ApplicationStatusHistory::where('application_id', $id)->count());
    }

    public function test_lamaran_yang_sudah_dibatalkan_tidak_dapat_diajukan_kembali_di_halaman_detail_proyek(): void
    {
        $kandidat = $this->makeKandidat();
        $lamaran = $this->makeApplicationFor($kandidat, ApplicationStatus::Pending);
        $id = $lamaran->id;
        $projectId = $lamaran->project_id;

        $this->browse(function (Browser $browser) use ($kandidat, $id, $projectId) {
            $browser->loginAs($kandidat)
                ->visit(route('kandidat.lamaran.index'))
                ->click("@batalkan-lamaran-{$id}")
                ->waitFor("@dialog-batal-{$id}")
                ->press("@konfirmasi-batal-{$id}")
                ->waitForText('Lamaran berhasil dibatalkan')

                ->visit(route('kandidat.proyek.show', $projectId))
                ->assertSeeIn('@status-lamaran', 'Lamaran Anda sudah dibatalkan')
                ->assertSeeIn('@status-lamaran', 'tidak dapat diajukan kembali pada proyek ini')
                ->assertDisabled('@pesan-lamaran')
                ->assertDisabled('@kirim-lamaran');
        });

        $this->assertSame(1, Application::where('project_id', $projectId)->count());
    }

    // =====================================================================
    // Alur negatif
    // =====================================================================

    public function test_menutup_dialog_tidak_mengubah_lamaran(): void
    {
        $kandidat = $this->makeKandidat();
        $lamaran = $this->makeApplicationFor($kandidat, ApplicationStatus::Pending);
        $id = $lamaran->id;

        $this->browse(function (Browser $browser) use ($kandidat, $id) {
            $browser->loginAs($kandidat)
                ->visit(route('kandidat.lamaran.index'))

                // "Tidak, Kembali" menutup dialog.
                ->click("@batalkan-lamaran-{$id}")
                ->waitFor("@dialog-batal-{$id}")
                ->click("@tutup-batal-{$id}")
                ->waitUntilMissing("@dialog-batal-{$id}")
                ->assertPresent("@batalkan-lamaran-{$id}")

                // Tombol Escape juga menutup dialog.
                ->click("@batalkan-lamaran-{$id}")
                ->waitFor("@dialog-batal-{$id}")
                ->keys("@tutup-batal-{$id}", ['{escape}'])
                ->waitUntilMissing("@dialog-batal-{$id}")
                ->assertDontSee('Lamaran berhasil dibatalkan');
        });

        $this->assertSame(ApplicationStatus::Pending, Application::findOrFail($id)->status);
        $this->assertSame(1, ApplicationStatusHistory::where('application_id', $id)->count());
    }

    public function test_tombol_batalkan_tidak_tersedia_pada_status_diterima_ditolak_dan_dibatalkan(): void
    {
        $kandidat = $this->makeKandidat();
        $aktif = $this->makeApplicationFor($kandidat, ApplicationStatus::Pending);
        $diterima = $this->makeApplicationFor($kandidat, ApplicationStatus::Accepted);
        $ditolak = $this->makeApplicationFor($kandidat, ApplicationStatus::Rejected);
        $dibatalkan = $this->makeApplicationFor($kandidat, ApplicationStatus::Cancelled);

        $this->browse(function (Browser $browser) use ($kandidat, $aktif, $diterima, $ditolak, $dibatalkan) {
            $browser->loginAs($kandidat)
                ->visit(route('kandidat.lamaran.index'))
                ->assertPresent("@batalkan-lamaran-{$aktif->id}")      // pembanding: yang aktif punya tombol
                ->assertMissing("@batalkan-lamaran-{$diterima->id}")
                ->assertMissing("@batalkan-lamaran-{$ditolak->id}")
                ->assertMissing("@batalkan-lamaran-{$dibatalkan->id}")
                ->assertSeeIn("#lamaran-{$diterima->id}", 'tidak dapat dibatalkan');
        });
    }

    public function test_pembatalan_ditolak_bila_umkm_sudah_menerima_lamaran_saat_dialog_terbuka(): void
    {
        $kandidat = $this->makeKandidat();
        $lamaran = $this->makeApplicationFor($kandidat, ApplicationStatus::Shortlisted);
        $id = $lamaran->id;

        $this->browse(function (Browser $browser) use ($kandidat, $id) {
            $browser->loginAs($kandidat)
                ->visit(route('kandidat.lamaran.index'))
                ->click("@batalkan-lamaran-{$id}")
                ->waitFor("@dialog-batal-{$id}");

            // Saat dialog masih terbuka, UMKM menerima lamaran ini.
            Application::whereKey($id)->update(['status' => ApplicationStatus::Accepted->value]);

            $browser->press("@konfirmasi-batal-{$id}")
                ->waitForText('Lamaran yang sudah diterima tidak dapat dibatalkan.')
                ->assertDontSee('Lamaran berhasil dibatalkan')
                ->assertMissing("@batalkan-lamaran-{$id}");
        });

        $this->assertSame(ApplicationStatus::Accepted, Application::findOrFail($id)->status);
        $this->assertSame(1, ApplicationStatusHistory::where('application_id', $id)->count());
    }

    public function test_kandidat_lain_tidak_melihat_lamaran_atau_tombol_milik_orang_lain(): void
    {
        $pemilik = $this->makeKandidat();
        $lain = $this->makeKandidat();
        $judul = $this->judul('Dusk Milik Pemilik');
        $lamaran = $this->makeApplicationFor($pemilik, ApplicationStatus::Pending, ['title' => $judul]);

        $this->browse(function (Browser $browser) use ($lain, $judul, $lamaran) {
            $browser->loginAs($lain)
                ->visit(route('kandidat.lamaran.index'))
                ->assertDontSee($judul)
                ->assertMissing("@batalkan-lamaran-{$lamaran->id}");
        });

        $this->assertSame(ApplicationStatus::Pending, $lamaran->fresh()->status);
    }

    // =====================================================================
    // Responsif
    // =====================================================================

    public function test_pembatalan_dapat_dilakukan_pada_layar_smartphone(): void
    {
        $kandidat = $this->makeKandidat();
        $lamaran = $this->makeApplicationFor($kandidat, ApplicationStatus::Pending);
        $id = $lamaran->id;

        $this->browse(function (Browser $browser) use ($kandidat, $id) {
            try {
                $browser->resize(375, 812)
                    ->loginAs($kandidat)
                    ->visit(route('kandidat.lamaran.index'))
                    ->scrollIntoView("@batalkan-lamaran-{$id}")
                    ->click("@batalkan-lamaran-{$id}")
                    ->waitFor("@dialog-batal-{$id}")
                    ->assertVisible("@tutup-batal-{$id}")
                    ->assertVisible("@konfirmasi-batal-{$id}")
                    ->press("@konfirmasi-batal-{$id}")
                    ->waitForText('Lamaran berhasil dibatalkan');
            } finally {
                $browser->resize(1920, 1080); // pulihkan ukuran jendela untuk test berikutnya
            }
        });

        $this->assertSame(ApplicationStatus::Cancelled, Application::findOrFail($id)->status);
    }
}
