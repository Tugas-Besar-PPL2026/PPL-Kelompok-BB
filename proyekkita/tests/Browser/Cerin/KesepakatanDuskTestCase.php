<?php

namespace Tests\Browser\Cerin;

use Carbon\Carbon;
use Laravel\Dusk\Browser;

/**
 * Dasar test Dusk PBI-24, PBI-25, dan PBI-26 (Pengelolaan Kesepakatan) milik Cerin.
 *
 * Mewarisi CerinDuskTestCase (login lewat form, data dibuat lewat UI, jeda DUSK_JEDA, bukti screenshot),
 * lalu menambah langkah persiapan khusus kesepakatan:
 *   pelamar diterima -> UMKM klik "Susun Kesepakatan" -> UMKM kirim -> kandidat menanggapi.
 * Semua data dibuat lewat browser seperti pengguna sungguhan: tanpa factory, seeder, SQL, atau loginAs().
 */
abstract class KesepakatanDuskTestCase extends CerinDuskTestCase
{
    protected const RUANG_LINGKUP = 'Memperbarui label kemasan tiga varian biji kopi dan menyusun katalog produk digital PDF';
    protected const HASIL = ['Label 3 SKU siap cetak', 'Katalog digital 8 halaman', 'File sumber Figma'];
    protected const BATAS_REVISI = '2';

    /* =========================================================================================
     |  Nilai bantu
     * ========================================================================================= */

    /** Tenggat contoh dari test case (15-12-2026). Bila tanggal itu sudah lewat, dipakai 21 hari dari hari ini. */
    protected function tenggat(): Carbon
    {
        $contoh = Carbon::parse('2026-12-15');

        return $contoh->isFuture() ? $contoh : now()->addDays(21)->startOfDay();
    }

    /** Format tanggal seperti di aplikasi, mis. "15 Des 2026". */
    protected function tanggalTampil(Carbon $tanggal): string
    {
        return $tanggal->copy()->locale('id')->translatedFormat('j M Y');
    }

    /** Isian kesepakatan lengkap dan valid. */
    protected function isiValid(): array
    {
        return [
            'scope' => self::RUANG_LINGKUP,
            'deliverables' => implode("\n", self::HASIL),
            'deadline' => $this->tenggat()->toDateString(),
            'revision_limit' => self::BATAS_REVISI,
        ];
    }

    /** Angka pada badge tab, mis. tab "Draf 1" -> 1. */
    protected function jumlahTab(Browser $browser, string $kunci): int
    {
        return (int) $browser->script(sprintf(
            "const s = document.querySelector('[dusk=\"tab-%s\"] span'); return s ? s.innerText.trim() : '-1';",
            $kunci
        ))[0];
    }

    /** ID kesepakatan dari URL saat ini, mis. /umkm/kesepakatan/12/susun -> 12. */
    protected function idKesepakatanDariUrl(Browser $browser): int
    {
        preg_match('#/kesepakatan/(\d+)#', (string) $browser->driver->getCurrentURL(), $m);
        $this->assertNotEmpty($m[1] ?? null, 'ID kesepakatan tidak ditemukan pada URL.');

        return (int) $m[1];
    }

    /* =========================================================================================
     |  Navigasi lewat menu
     * ========================================================================================= */

    /** Klik menu samping "Kesepakatan & Progres". $peran: umkm | kandidat. */
    protected function bukaMenuKesepakatan(Browser $browser, string $peran): Browser
    {
        return $browser->clickLink('Kesepakatan & Progres')
            ->waitForLocation("/{$peran}/kesepakatan", 20)
            ->waitFor('@tab-kesepakatan', 20);
    }

    protected function klikTab(Browser $browser, string $kunci): Browser
    {
        // Selector ditulis lengkap: "@tab-draft.on" akan dibaca Dusk sebagai dusk="tab-draft.on".
        return $browser->click("@tab-{$kunci}")->waitFor("[dusk=\"tab-{$kunci}\"].on", 20);
    }

    /** UMKM: menu Kesepakatan & Progres -> tab Draf -> Buka. */
    protected function bukaDrafLewatMenu(Browser $browser, int $kesepakatanId): Browser
    {
        $this->bukaMenuKesepakatan($browser, 'umkm');
        $this->klikTab($browser, 'draft');

        return $browser->click("@buka-kesepakatan-{$kesepakatanId}")
            ->waitForLocation("/umkm/kesepakatan/{$kesepakatanId}/susun", 20)
            ->waitFor('@form-kesepakatan', 20);
    }

    /* =========================================================================================
     |  Formulir Susun Kesepakatan (PBI-24)
     * ========================================================================================= */

    /** Isi kolom yang disebut saja. Nilai '' = kolom dikosongkan. Tanggal diisi lewat JavaScript. */
    protected function isiKesepakatan(Browser $browser, array $isi): Browser
    {
        foreach (['scope' => '@ruang-lingkup', 'deliverables' => '@hasil-diserahkan', 'revision_limit' => '@batas-revisi'] as $kolom => $selector) {
            if (! array_key_exists($kolom, $isi)) {
                continue;
            }
            $browser->clear($selector);
            if ($isi[$kolom] !== '') {
                $browser->type($selector, $isi[$kolom]);
            }
        }

        if (array_key_exists('deadline', $isi)) {
            $browser->script("document.getElementById('deadline').value = ".json_encode($isi['deadline']).';');
        }

        return $browser;
    }

    protected function klikKirim(Browser $browser): Browser
    {
        $browser->script('window.scrollTo(0, 0);');

        return $browser->waitForReload(fn (Browser $b) => $b->click('@kirim-kesepakatan'), 20);
    }

    protected function klikSimpanDraf(Browser $browser): Browser
    {
        $browser->script('window.scrollTo(0, 0);');

        return $browser->waitForReload(fn (Browser $b) => $b->click('@simpan-draf'), 20);
    }

    /* =========================================================================================
     |  Panel Tanggapan Anda (PBI-25)
     * ========================================================================================= */

    /**
     * $pilihan: setujui | tolak | null (tidak memilih). $alasan: '' = dibiarkan kosong. $centang: centang pemahaman.
     */
    protected function isiTanggapan(Browser $browser, ?string $pilihan, string $alasan = '', bool $centang = false): Browser
    {
        if ($pilihan !== null) {
            $browser->click("@opsi-{$pilihan}");
        }

        $browser->clear('@alasan-penolakan');
        if ($alasan !== '') {
            $browser->type('@alasan-penolakan', $alasan);
        }

        if ($centang) {
            $browser->check('@pemahaman');
        }

        return $browser;
    }

    protected function klikKirimTanggapan(Browser $browser): Browser
    {
        return $browser->waitForReload(fn (Browser $b) => $b->click('@kirim-tanggapan'), 20);
    }

    /* =========================================================================================
     |  Persiapan data lewat UI
     * ========================================================================================= */

    /**
     * UMKM terverifikasi + 1 proyek terbit (Rp2.500.000, 2 termin) + kandidat melamar lalu Diterima.
     *
     * @return array{umkm: array, proyek: array, kandidat: array, lamaran: int}
     */
    protected function siapkanPelamarDiterima(Browser $browser, int $kuota = 2, string $labelKandidat = 'K1'): array
    {
        $umkm = $this->siapkanUmkmTerverifikasi($browser, 'UMKM A');
        $proyek = $this->siapkanProyekTerbit($browser, $umkm, 'Proyek', $kuota, true);
        $kandidat = $this->daftarKandidat($browser, $labelKandidat);
        $this->lamar($browser, $kandidat, $proyek);
        $lamaran = $this->putuskan($browser, $umkm, $proyek, $kandidat, 'accept');

        return compact('umkm', 'proyek', 'kandidat', 'lamaran');
    }

    /** UMKM klik "Susun Kesepakatan" pada detail pelamar yang sudah Diterima. Diakhiri logout. */
    protected function susunKesepakatan(Browser $browser, array $umkm, int $lamaranId): int
    {
        return $this->persiapan("susun kesepakatan lamaran {$lamaranId}", function () use ($browser, $umkm, $lamaranId) {
            $this->masuk($browser, $umkm);
            $browser->visit("/umkm/seleksi/pelamar/{$lamaranId}")
                ->waitFor('@susun-kesepakatan', 20)
                ->click('@susun-kesepakatan')
                ->waitFor('@form-kesepakatan', 20);
            $id = $this->idKesepakatanDariUrl($browser);
            $this->keluar($browser);

            return $id;
        });
    }

    /** UMKM mengisi lalu menyimpan draf (Simpan Draf). Diakhiri logout. */
    protected function simpanDrafSebagaiUmkm(Browser $browser, array $umkm, int $kesepakatanId, array $isi): void
    {
        $this->persiapan("simpan draf kesepakatan {$kesepakatanId}", function () use ($browser, $umkm, $kesepakatanId, $isi) {
            $this->masuk($browser, $umkm);
            $browser->visit("/umkm/kesepakatan/{$kesepakatanId}/susun")->waitFor('@form-kesepakatan', 20);
            $this->isiKesepakatan($browser, $isi);
            $this->klikSimpanDraf($browser);
            $browser->assertSee('Draf kesepakatan berhasil disimpan.');
            $this->keluar($browser);
        });
    }

    /** UMKM mengisi isian valid lalu menekan Kirim ke Kandidat. Diakhiri logout. */
    protected function kirimSebagaiUmkm(Browser $browser, array $umkm, int $kesepakatanId): void
    {
        $this->persiapan("kirim kesepakatan {$kesepakatanId}", function () use ($browser, $umkm, $kesepakatanId) {
            $this->masuk($browser, $umkm);
            $browser->visit("/umkm/kesepakatan/{$kesepakatanId}/susun")->waitFor('@form-kesepakatan', 20);
            $this->isiKesepakatan($browser, $this->isiValid());
            $this->klikKirim($browser);
            $browser->assertSee('Kesepakatan berhasil dikirim ke kandidat.');
            $this->keluar($browser);
        });
    }

    /** Kandidat menanggapi kesepakatan (setujui | tolak). Diakhiri logout. */
    protected function tanggapiSebagaiKandidat(Browser $browser, array $kandidat, int $kesepakatanId, string $pilihan, string $alasan = ''): void
    {
        $this->persiapan("{$kandidat['nama']} {$pilihan} kesepakatan {$kesepakatanId}", function () use ($browser, $kandidat, $kesepakatanId, $pilihan, $alasan) {
            $this->masuk($browser, $kandidat);
            $browser->visit("/kandidat/kesepakatan/{$kesepakatanId}/tanggapi")->waitFor('@form-tanggapan', 20);
            $this->isiTanggapan($browser, $pilihan, $alasan, $pilihan === 'setujui');
            $this->klikKirimTanggapan($browser);
            $browser->assertSee('Tanggapan berhasil dikirim.');
            $this->keluar($browser);
        });
    }

    /**
     * Pelamar Diterima + draf kesepakatan. $tahap: draf | dikirim | aktif | ditolak.
     *
     * @return array{umkm: array, proyek: array, kandidat: array, lamaran: int, kesepakatan: int}
     */
    protected function siapkanKesepakatan(Browser $browser, string $tahap, int $kuota = 2, string $labelKandidat = 'K1', string $alasanTolak = 'Tenggat terlalu singkat'): array
    {
        $data = $this->siapkanPelamarDiterima($browser, $kuota, $labelKandidat);
        $data['kesepakatan'] = $this->susunKesepakatan($browser, $data['umkm'], $data['lamaran']);

        if ($tahap !== 'draf') {
            $this->kirimSebagaiUmkm($browser, $data['umkm'], $data['kesepakatan']);
        }
        if ($tahap === 'aktif') {
            $this->tanggapiSebagaiKandidat($browser, $data['kandidat'], $data['kesepakatan'], 'setujui');
        }
        if ($tahap === 'ditolak') {
            $this->tanggapiSebagaiKandidat($browser, $data['kandidat'], $data['kesepakatan'], 'tolak', $alasanTolak);
        }

        return $data;
    }

    /**
     * Kandidat lain pada proyek yang sudah ada: daftar, melamar, Diterima, lalu kesepakatan sampai $tahap.
     *
     * @return array{kandidat: array, lamaran: int, kesepakatan: int}
     */
    protected function tambahKesepakatan(Browser $browser, array $umkm, array $proyek, string $labelKandidat, string $tahap, string $alasanTolak = 'Tenggat terlalu singkat', ?array $kandidat = null): array
    {
        $kandidat ??= $this->daftarKandidat($browser, $labelKandidat);
        $this->lamar($browser, $kandidat, $proyek);
        $lamaran = $this->putuskan($browser, $umkm, $proyek, $kandidat, 'accept');
        $kesepakatan = $this->susunKesepakatan($browser, $umkm, $lamaran);

        if ($tahap !== 'draf') {
            $this->kirimSebagaiUmkm($browser, $umkm, $kesepakatan);
        }
        if ($tahap === 'aktif') {
            $this->tanggapiSebagaiKandidat($browser, $kandidat, $kesepakatan, 'setujui');
        }
        if ($tahap === 'ditolak') {
            $this->tanggapiSebagaiKandidat($browser, $kandidat, $kesepakatan, 'tolak', $alasanTolak);
        }

        return compact('kandidat', 'lamaran', 'kesepakatan');
    }

    /**
     * Data lengkap PBI-26 (precondition Excel):
     *  - UMKM punya Proyek A (kuota 4) dan Proyek B (kuota 1), keduanya terbit
     *  - Kandidat 1: Proyek A Aktif, Proyek B Dikirim
     *  - Kandidat 2: Proyek A Aktif
     *  - Kandidat 3: Proyek A Ditolak dengan alasan "Tenggat terlalu singkat"
     *  - Kandidat 4: Proyek A masih Draf
     */
    protected function siapkanDataLengkap(Browser $browser): array
    {
        $umkm = $this->siapkanUmkmTerverifikasi($browser, 'UMKM A');
        $proyekA = $this->siapkanProyekTerbit($browser, $umkm, 'Proyek A', 4, true);
        $proyekB = $this->siapkanProyekTerbit($browser, $umkm, 'Proyek B', 1, true);

        $k1a = $this->tambahKesepakatan($browser, $umkm, $proyekA, 'K1', 'aktif');
        $k1b = $this->tambahKesepakatan($browser, $umkm, $proyekB, 'K1', 'dikirim', kandidat: $k1a['kandidat']);
        $k2 = $this->tambahKesepakatan($browser, $umkm, $proyekA, 'K2', 'aktif');
        $k3 = $this->tambahKesepakatan($browser, $umkm, $proyekA, 'K3', 'ditolak', 'Tenggat terlalu singkat');
        $k4 = $this->tambahKesepakatan($browser, $umkm, $proyekA, 'K4', 'draf');

        return compact('umkm', 'proyekA', 'proyekB', 'k1a', 'k1b', 'k2', 'k3', 'k4');
    }
}
