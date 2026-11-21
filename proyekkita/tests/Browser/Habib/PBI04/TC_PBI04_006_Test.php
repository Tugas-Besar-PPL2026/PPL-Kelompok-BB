<?php

namespace Tests\Browser\Habib\PBI04;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\Browser\Habib\Concerns\ProfilKandidatE2E;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

/**
 * TC-PBI04-006 (sheet PBI-04, Test_Case_ProyekKita_PBI03-05.xlsx).
 * Durasi pengalaman kerja tidak dapat diisi dengan nilai yang tidak valid.
 *
 * Form pengalaman ada di tab "Keterampilan & Pengalaman" (/kandidat/profil): field #work_type
 * (Bidang / jenis pekerjaan, wajib), #duration (Durasi, wajib, teks bebas maks. 50), #exp_description
 * (Deskripsi pekerjaan = "hasil pekerjaan" pada Excel, opsional).
 *
 * TEMUAN: implementasi saat ini (CandidateExperienceRequest) hanya mewajibkan durasi berupa teks (required|string|max:50),
 * tanpa aturan nilai/format. Nilai negatif, nol, atau teks bebas diterima dan disimpan. Test ini tetap menguji Expected
 * Result Excel ("sistem menolak durasi tidak valid dan tidak menyimpan data") sehingga GAGAL sampai validasi durasi
 * ditambahkan. Expected Result tidak diubah agar test lolos. Lihat README bagian Temuan.
 */
#[Group('e2e-profil-kandidat')]
#[Group('pbi04')]
#[Group('TC-PBI04-006')]
#[Group('temuan-aplikasi')]
class TC_PBI04_006_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use ProfilKandidatE2E;
    use UsesDuskTestDatabase;

    private const BIDANG = 'Desain Grafis';

    private const DURASI = '6 bulan';

    private const HASIL = 'Membuat materi promosi digital untuk usaha kecil.';

    public function test_TC_PBI04_006_durasi_tidak_valid_ditolak(): void
    {
        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatProfil($browser, 'Citra Maharani');
            $this->masukSebagaiKandidat($browser, $kandidat);

            // Input Excel: nilai negatif, nol, dan format tidak sesuai - diuji terpisah.
            $durasiTidakValid = ['negatif' => '-3 bulan', 'nol' => '0 bulan', 'format tidak sesuai' => 'abc'];
            $diterima = [];

            foreach ($durasiTidakValid as $jenis => $durasi) {
                // Langkah 1-3: field lain valid, durasi tidak valid, klik simpan.
                $this->bukaTabKeterampilanPengalaman($browser);
                $this->isiFormPengalaman($browser, "Desain Grafis ({$jenis})", $durasi, self::HASIL);
                $this->simpanPengalaman($browser);

                // Langkah 4: amati pesan validasi pada field Durasi.
                if ($this->galatField($browser, 'duration') === null) {
                    $diterima[] = "{$jenis} \"{$durasi}\"";
                }
            }

            $tersimpan = array_map(fn ($e) => $e->duration, $this->pengalamanDb($kandidat['email']));

            // Expected: setiap durasi tidak valid ditolak dengan pesan validasi dan tidak tersimpan.
            $this->assertSame([], $diterima,
                'Durasi tidak valid diterima aplikasi (tidak ada pesan validasi): '.implode(', ', $diterima)
                .'. Tersimpan di profil: '.json_encode($tersimpan).'.');
            $this->assertSame([], $tersimpan, 'Pengalaman dengan durasi tidak valid tidak boleh tersimpan.');
        });
    }
}
