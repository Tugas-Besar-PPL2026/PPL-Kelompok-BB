<?php

namespace Tests\Browser\Concerns;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Dusk\Browser;

/**
 * [PBI-31] Persiapan dan langkah E2E penyerahan hasil pekerjaan.
 *
 * Alur yang dibuat LEWAT UI (memakai helper existing AlurE2EProyekKita / PersiapanLamaranE2E):
 *   UMKM membuat & mengajukan proyek -> Admin memoderasi -> Kandidat melamar -> UMKM menerima kandidat.
 *
 * Simulasi database MINIMUM (dokumentasi exception), hanya untuk kondisi yang fiturnya belum ada di repository:
 *   - Baris agreements (status aktif/draf/terkirim/ditolak/selesai/dibatalkan): PBI-24/25 belum diimplementasikan.
 *   - Status/catatan revisi penyerahan (approved, revision_requested + revision_requests): PBI-32 belum ada.
 *   - Versi 2 penyerahan: PBI-33 belum ada.
 *   - Laporan progres: PBI-29 belum ada.
 *   - Pemicu (trigger) MySQL sementara untuk mensimulasikan kegagalan database saat menyimpan penyerahan.
 * Semua simulasi bersifat opt-in lewat DUSK_IZINKAN_SIMULASI_DB=true (sama dengan butuhSimulasiDb() PBI sebelumnya).
 * Alur yang diuji (membuka halaman, mengisi form, mengunggah, mengirim, melihat riwayat, mengunduh) selalu lewat browser.
 */
trait PersiapanPenyerahanE2E
{
    protected const MSG_SUKSES_V1 = 'Hasil pekerjaan Versi 1 berhasil diserahkan dan berstatus Menunggu Tinjauan.';

    protected const MSG_SEDANG_DITINJAU = 'Versi 1 sedang ditinjau UMKM.';

    protected const MSG_TOLAK_DITINJAU = 'Hasil pekerjaan Versi 1 sedang ditinjau UMKM. Versi baru belum dapat dikirim.';

    protected const MSG_DISETUJUI = 'Hasil pekerjaan sudah disetujui UMKM.';

    protected const MSG_TOLAK_DISETUJUI = 'Hasil pekerjaan sudah disetujui UMKM. Penyerahan baru tidak diperlukan.';

    protected const MSG_ISI_MINIMAL = 'Isi minimal salah satu: berkas hasil pekerjaan atau tautan hasil pekerjaan.';

    protected const MSG_DESKRIPSI_WAJIB = 'Deskripsi hasil pekerjaan wajib diisi.';

    protected const MSG_DESKRIPSI_MAKS = 'Deskripsi hasil pekerjaan maksimal 2000 karakter.';

    protected const MSG_FORMAT = 'Berkas hasil pekerjaan harus berformat PDF, JPG, PNG, atau ZIP.';

    protected const MSG_UKURAN = 'Ukuran berkas hasil pekerjaan maksimal 25 MB.';

    protected const MSG_KOSONG = 'Berkas hasil pekerjaan kosong dan tidak dapat diunggah.';

    protected const MSG_GAGAL_UNGGAH = 'Berkas hasil pekerjaan gagal diunggah. Silakan coba lagi.';

    protected const MSG_TAUTAN_TIDAK_VALID = 'Tautan hasil pekerjaan harus berupa URL http/https yang valid.';

    protected const MSG_TAUTAN_MAKS = 'Tautan hasil pekerjaan maksimal 255 karakter.';

    protected const MSG_GAGAL_SIMPAN = 'Hasil pekerjaan gagal disimpan. Silakan coba lagi.';

    protected const MSG_BERKAS_TIDAK_ADA = 'Berkas hasil pekerjaan tidak ditemukan.';

    /** Pesan penolakan per status kesepakatan (BR-31-03). */
    protected const MSG_STATUS_KESEPAKATAN = [
        'draft' => 'Kesepakatan belum aktif. Hasil pekerjaan dapat diserahkan setelah kesepakatan disetujui.',
        'sent' => 'Kesepakatan belum aktif. Hasil pekerjaan dapat diserahkan setelah kesepakatan disetujui.',
        'rejected' => 'Kesepakatan ditolak sehingga hasil pekerjaan tidak dapat diserahkan.',
        'completed' => 'Kesepakatan sudah selesai. Hasil pekerjaan tidak dapat diserahkan lagi.',
        'cancelled' => 'Kesepakatan sudah dibatalkan. Hasil pekerjaan tidak dapat diserahkan.',
    ];

    // ------------------------------------------------------------------
    // Persiapan
    // ------------------------------------------------------------------

    /**
     * Membuat N kesepakatan milik $kandidat. Proyek, lamaran, dan penerimaan dibuat lewat UI; baris kesepakatan
     * disimulasikan (PBI-24/25 belum ada). Diakhiri tanpa sesi login.
     *
     * @param  array<string, array<string, mixed>>  $rencana  label => kolom agreements yang ditimpa (mis. ['status' => 'draft'])
     * @return array<string, array{judul: string, proyek: int, lamaran: int, kesepakatan: int}>
     */
    protected function siapkanKesepakatanLewatUi(Browser $browser, array $kandidat, array $rencana, string $tc): array
    {
        $this->butuhSimulasiKesepakatan($tc);

        $kode = $this->kodeUnik();
        $judul = [];
        foreach (array_keys($rencana) as $label) {
            $judul[$label] = "Dusk {$tc} {$label} {$kode}";
        }

        $proyek = $this->terbitkanProyek($browser, array_values($judul));
        $this->kandidatMelamarLewatUi($browser, $kandidat, array_values($judul));
        $this->umkmMemutuskanLewatUi(
            $browser,
            $kandidat['nama'],
            array_map(fn (string $j) => [$proyek[$j], 'accept', null], array_values($judul))
        );

        $hasil = [];
        foreach ($rencana as $label => $kolom) {
            $j = $judul[$label];
            $lamaran = $this->lamaranDb($kandidat['email'], $proyek[$j]);
            $this->assertNotNull($lamaran, "Lamaran untuk {$j} tidak ditemukan.");
            $this->assertSame('accepted', $lamaran->status, "Lamaran {$j} belum diterima lewat UI.");

            $hasil[$label] = [
                'judul' => $j,
                'proyek' => (int) $proyek[$j],
                'lamaran' => (int) $lamaran->id,
                'kesepakatan' => $this->simulasikanKesepakatan((int) $lamaran->id, $kolom),
            ];
        }

        return $hasil;
    }

    protected function butuhSimulasiKesepakatan(string $tc): void
    {
        if (! filter_var(env('DUSK_IZINKAN_SIMULASI_DB', false), FILTER_VALIDATE_BOOL)) {
            $this->markTestSkipped(
                "{$tc}: membutuhkan kesepakatan (agreements) yang belum dapat dibuat lewat UI karena PBI-24/25 belum "
                .'diimplementasikan. Set DUSK_IZINKAN_SIMULASI_DB=true untuk menjalankan.'
            );
        }
    }

    /** Simulasi PBI-24/25: satu baris agreements untuk lamaran yang sudah diterima lewat UI. */
    protected function simulasikanKesepakatan(int $lamaranId, array $kolom = []): int
    {
        $status = $kolom['status'] ?? 'active';
        $sudahDisetujui = in_array($status, ['active', 'completed', 'cancelled'], true);

        return (int) DB::table('agreements')->insertGetId(array_merge([
            'application_id' => $lamaranId,
            'scope' => 'Redesain katalog digital dan label kemasan (data uji Dusk PBI-31).',
            'deliverables' => 'Katalog digital (PDF) dan aset label kemasan 3 SKU (ZIP).',
            'deadline' => Carbon::today()->addDays(30)->toDateString(),
            'total_compensation' => 1500000,
            'revision_limit' => 2,
            'status' => $status,
            'rejection_reason' => $status === 'rejected' ? 'Ruang lingkup belum sesuai (data uji).' : null,
            'sent_at' => $status === 'draft' ? null : now()->subDays(2),
            'approved_at' => $sudahDisetujui ? now()->subDay() : null,
        ], $kolom));
    }

    /** Simulasi perubahan status kesepakatan oleh pihak lain (mis. pembatalan PBI-28) saat halaman sudah terbuka. */
    protected function simulasikanStatusKesepakatan(int $kesepakatanId, string $status): void
    {
        DB::table('agreements')->where('id', $kesepakatanId)->update(['status' => $status]);
    }

    /** Simulasi PBI-32: UMKM menyetujui atau meminta revisi atas suatu versi. */
    protected function simulasikanKeputusanPenyerahan(int $penyerahanId, string $status, ?string $catatan = null): void
    {
        DB::table('submissions')->where('id', $penyerahanId)->update(['status' => $status]);

        if ($catatan !== null) {
            DB::table('revision_requests')->insert([
                'submission_id' => $penyerahanId,
                'note' => $catatan,
                'created_at' => now(),
            ]);
        }
    }

    /** Simulasi PBI-33: versi perbaikan yang dikirim setelah revisi diminta. */
    protected function simulasikanVersiPerbaikan(int $kesepakatanId, int $versi, string $tautan, string $deskripsi): int
    {
        return (int) DB::table('submissions')->insertGetId([
            'agreement_id' => $kesepakatanId,
            'version_number' => $versi,
            'file_path' => null,
            'result_link' => $tautan,
            'description' => $deskripsi,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }

    /** Simulasi PBI-29: laporan progres pengerjaan. */
    protected function simulasikanProgres(int $kesepakatanId, int $persen): void
    {
        DB::table('progress_updates')->insert([
            'agreement_id' => $kesepakatanId,
            'note' => "Progres {$persen}% (data uji Dusk PBI-31).",
            'percentage' => $persen,
            'created_at' => now(),
        ]);
    }

    /**
     * Simulasi kegagalan database: trigger MySQL sementara yang menolak INSERT penyerahan untuk SATU kesepakatan.
     * Selalu lepaskan dengan lepasPemicuGagalSimpan() di blok finally.
     */
    protected function pasangPemicuGagalSimpan(int $kesepakatanId): string
    {
        $nama = 'dusk_pbi31_gagal_'.$kesepakatanId;
        DB::unprepared("DROP TRIGGER IF EXISTS {$nama}");
        DB::unprepared(
            "CREATE TRIGGER {$nama} BEFORE INSERT ON submissions FOR EACH ROW "
            ."BEGIN IF NEW.agreement_id = {$kesepakatanId} THEN "
            ."SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Simulasi kegagalan database (Dusk PBI-31)'; "
            .'END IF; END'
        );

        return $nama;
    }

    protected function lepasPemicuGagalSimpan(string $nama): void
    {
        DB::unprepared("DROP TRIGGER IF EXISTS {$nama}");
    }

    // ------------------------------------------------------------------
    // Langkah lewat browser
    // ------------------------------------------------------------------

    /**
     * Kandidat (sudah login) membuka Penyerahan Hasil lewat menu Lamaran Saya -> kartu lamaran -> "Penyerahan Hasil".
     * Catatan: daftar/detail kesepakatan (PBI-26) belum ada, sehingga pintu masuk sementara ada di kartu Lamaran Saya.
     */
    protected function bukaPenyerahanHasil(Browser $browser, string $judulProyek): Browser
    {
        $this->bukaLamaranSaya($browser);
        $lamaranId = $this->idLamaranDariKartu($browser, $judulProyek);

        $this->tandaiHalamanLama($browser);
        $browser->click("@penyerahan-hasil-{$lamaranId}");

        return $this->tungguHalamanBaru($browser)
            ->waitFor('@halaman-penyerahan')
            ->assertSee('Penyerahan Hasil Pekerjaan')
            ->assertSeeIn('@judul-proyek', $judulProyek);
    }

    protected function urlPenyerahan(int $kesepakatanId): string
    {
        return "/kandidat/kesepakatan/{$kesepakatanId}/penyerahan";
    }

    protected function urlBerkas(int $kesepakatanId, int $penyerahanId): string
    {
        return "/kandidat/kesepakatan/{$kesepakatanId}/penyerahan/{$penyerahanId}/berkas";
    }

    /**
     * Mengisi form penyerahan. null = biarkan kolom kosong. Teks panjang (> 200) atau berisi karakter di luar BMP
     * (emoji) diisi lewat script karena ChromeDriver tidak dapat mengetik karakter tersebut; event input tetap dipicu.
     */
    protected function isiFormPenyerahan(Browser $browser, ?string $berkas, ?string $tautan, ?string $deskripsi): Browser
    {
        $browser->waitFor('@form-penyerahan');

        if ($berkas !== null) {
            $browser->attach('@input-berkas', $berkas)->waitFor('@berkas-terpilih');
        }

        if ($tautan !== null) {
            $this->isiTeks($browser, '@tautan-hasil', $tautan);
        }

        if ($deskripsi !== null) {
            $this->isiTeks($browser, '@deskripsi-hasil', $deskripsi);
        }

        return $browser;
    }

    protected function isiTeks(Browser $browser, string $dusk, string $nilai): Browser
    {
        if ($nilai === '') {
            return $browser->clear($dusk);
        }

        if (mb_strlen($nilai) <= 200 && ! preg_match('/[\x{10000}-\x{10FFFF}]/u', $nilai)) {
            return $browser->clear($dusk)->type($dusk, $nilai);
        }

        $browser->script(sprintf(
            "(() => { const el = document.querySelector('[dusk=\"%s\"]'); el.value = %s; el.dispatchEvent(new Event('input', {bubbles: true})); })();",
            ltrim($dusk, '@'),
            json_encode($nilai, JSON_UNESCAPED_UNICODE)
        ));

        return $browser;
    }

    /** Klik "Kirim Versi n" lalu menunggu halaman hasil redirect dimuat. */
    protected function kirimPenyerahan(Browser $browser): Browser
    {
        $this->tandaiHalamanLama($browser);
        $browser->press('@kirim-penyerahan');

        return $this->tungguHalamanBaru($browser)->waitFor('@halaman-penyerahan');
    }

    /** Menyerahkan Versi 1 lewat UI (dipakai sebagai precondition test lain). */
    protected function serahkanVersi1LewatUi(Browser $browser, string $judulProyek, ?string $berkas, ?string $tautan, string $deskripsi): void
    {
        $this->bukaPenyerahanHasil($browser, $judulProyek);
        $this->isiFormPenyerahan($browser, $berkas, $tautan, $deskripsi);
        $this->kirimPenyerahan($browser)->assertSee(self::MSG_SUKSES_V1);
    }

    /** Request langsung ke route penyerahan dari halaman saat ini (tanpa berkas), memakai helper existing. */
    protected function kirimPenyerahanLangsung(Browser $browser, int $kesepakatanId, array $isian): Browser
    {
        return $this->kirimFormManipulasi($browser, 'POST', $this->urlPenyerahan($kesepakatanId), $isian);
    }

    /**
     * Mengganti nama berkas yang sudah dipilih di input (DataTransfer) agar nama yang tidak valid di sistem berkas
     * Windows (mis. berisi < >) tetap dapat diuji.
     */
    protected function gantiNamaBerkasTerpilih(Browser $browser, string $namaBaru): Browser
    {
        $browser->script(sprintf(
            "(() => { const input = document.querySelector('[dusk=\"input-berkas\"]'); const f = input.files[0];"
            .' const dt = new DataTransfer(); dt.items.add(new File([f], %s, {type: f.type}));'
            ." input.files = dt.files; input.dispatchEvent(new Event('change', {bubbles: true})); })();",
            json_encode($namaBaru)
        ));

        return $browser->waitForTextIn('@nama-berkas-terpilih', $namaBaru);
    }

    /**
     * Mengambil URL lewat fetch() dari browser (memakai sesi login yang sama) dan mengembalikan status serta header.
     *
     * @return array{status: int, disposition: string, nosniff: string, type: string}
     */
    protected function ambilLewatBrowser(Browser $browser, string $url): array
    {
        $hasil = $browser->driver->executeAsyncScript(
            'const [url, selesai] = arguments;'
            ."fetch(url, {credentials: 'same-origin'}).then(r => selesai({status: r.status,"
            ." disposition: r.headers.get('content-disposition') || '', nosniff: r.headers.get('x-content-type-options') || '',"
            ." type: r.headers.get('content-type') || ''})).catch(e => selesai({status: 0, disposition: '', nosniff: '', type: String(e)}));",
            [$url]
        );

        return [
            'status' => (int) ($hasil['status'] ?? 0),
            'disposition' => (string) ($hasil['disposition'] ?? ''),
            'nosniff' => (string) ($hasil['nosniff'] ?? ''),
            'type' => (string) ($hasil['type'] ?? ''),
        ];
    }

    /** Halaman galat 404 atau 405 (route ubah/hapus versi memang tidak tersedia). */
    protected function assertHalamanGalat404Atau405(Browser $browser): void
    {
        $teks = $browser->driver->findElement(\Facebook\WebDriver\WebDriverBy::tagName('body'))->getText();

        $this->assertTrue(
            (str_contains($teks, '404') && stripos($teks, 'Not Found') !== false)
                || (str_contains($teks, '405') && stripos($teks, 'Method Not Allowed') !== false),
            'Halaman tidak menampilkan galat 404 Not Found / 405 Method Not Allowed.'
        );
    }

    // ------------------------------------------------------------------
    // Pembacaan database dan storage (verifikasi tambahan)
    // ------------------------------------------------------------------

    /** @return list<object> */
    protected function penyerahanDb(int $kesepakatanId): array
    {
        return DB::table('submissions')->where('agreement_id', $kesepakatanId)->orderBy('version_number')->get()->all();
    }

    /** Berkas di disk privat untuk satu kesepakatan. */
    protected function berkasPenyerahanDisk(int $kesepakatanId): array
    {
        return Storage::disk('local')->allFiles("submissions/{$kesepakatanId}");
    }

    /** Format waktu yang diharapkan di Riwayat Versi, dari nilai submitted_at (UTC) di database. */
    protected function waktuVersiDiharapkan(string $submittedAt): string
    {
        return 'Diserahkan '.Carbon::parse($submittedAt, 'UTC')->timezone('Asia/Jakarta')->locale('id')->translatedFormat('j M Y, H.i').' WIB';
    }
}
