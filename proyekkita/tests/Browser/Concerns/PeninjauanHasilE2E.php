<?php

namespace Tests\Browser\Concerns;

use Laravel\Dusk\Browser;

/**
 * [PBI-32 / PBI-33] Langkah E2E peninjauan hasil oleh UMKM dan siklus revisi.
 *
 * Melengkapi PersiapanPenyerahanE2E (PBI-31). Seluruh keputusan UMKM (setuju / minta revisi) dibuat lewat UI
 * halaman Tinjau Hasil, sehingga precondition PBI-33 (revisi diminta) TIDAK lagi disimulasikan di database.
 * Simulasi database yang tersisa sama dengan PBI-31: baris kesepakatan (PBI-24/25 belum ada), perubahan status
 * kesepakatan oleh pihak lain, trigger kegagalan database, dan penghapusan berkas dari disk.
 */
trait PeninjauanHasilE2E
{
    protected const MSG_SETUJU = 'Hasil pekerjaan Versi :v disetujui.';

    protected const MSG_REVISI_TERKIRIM = 'Permintaan revisi untuk Versi :v berhasil dikirim kepada kandidat.';

    protected const MSG_SUDAH_DISETUJUI = 'Hasil pekerjaan Versi :v sudah disetujui.';

    protected const MSG_MENUNGGU_PERBAIKAN = 'Versi :v sedang diperbaiki kandidat. Tunggu versi perbaikan untuk ditinjau.';

    protected const MSG_BELUM_ADA_PENYERAHAN = 'Kandidat belum menyerahkan hasil pekerjaan.';

    protected const MSG_VERSI_BERUBAH = 'Versi yang ditinjau sudah berubah. Muat ulang halaman lalu tinjau versi terbaru.';

    protected const MSG_BATAS_REVISI = 'Batas revisi sudah habis (:used dari :limit). Hasil pekerjaan hanya dapat disetujui.';

    protected const MSG_PILIH_KEPUTUSAN = 'Pilih keputusan peninjauan: setujui atau minta revisi.';

    protected const MSG_CATATAN_WAJIB = 'Catatan perbaikan wajib diisi saat meminta revisi.';

    protected const MSG_CATATAN_MAKS = 'Catatan maksimal 2000 karakter.';

    protected function pesan(string $template, array $nilai): string
    {
        return str_replace(array_map(fn ($k) => ':'.$k, array_keys($nilai)), array_values($nilai), $template);
    }

    protected function urlTinjau(int $kesepakatanId): string
    {
        return "/umkm/kesepakatan/{$kesepakatanId}/tinjau";
    }

    protected function urlBerkasUmkm(int $kesepakatanId, int $penyerahanId): string
    {
        return "/umkm/kesepakatan/{$kesepakatanId}/penyerahan/{$penyerahanId}/berkas";
    }

    /**
     * UMKM (sudah login) membuka Tinjau Hasil lewat menu Seleksi Kandidat -> pilih proyek -> Lihat pelamar -> "Tinjau Hasil".
     * Daftar/detail kesepakatan UMKM (PBI-26) belum ada, sehingga detail pelamar menjadi pintu masuk sementara.
     *
     * @param  array{judul: string, proyek: int, lamaran: int, kesepakatan: int}  $kesepakatan
     */
    protected function bukaTinjauHasil(Browser $browser, array $kesepakatan): Browser
    {
        $this->bukaSeleksiProyek($browser, $kesepakatan['proyek']);

        $this->tandaiHalamanLama($browser);
        $browser->click("@lihat-pelamar-{$kesepakatan['lamaran']}");
        $this->tungguHalamanBaru($browser)->waitFor("@tinjau-hasil-{$kesepakatan['lamaran']}");

        $this->tandaiHalamanLama($browser);
        $browser->click("@tinjau-hasil-{$kesepakatan['lamaran']}");

        return $this->tungguHalamanBaru($browser)
            ->waitFor('@halaman-tinjau')
            ->assertSee('Tinjau Hasil Pekerjaan')
            ->assertSeeIn('@judul-proyek', $kesepakatan['judul']);
    }

    /** UMKM (sudah di halaman Tinjau Hasil) memilih keputusan, mengisi catatan, lalu menyimpan. */
    protected function simpanKeputusanTinjau(Browser $browser, ?string $keputusan, ?string $catatan): Browser
    {
        $browser->waitFor('@form-keputusan');

        if ($keputusan !== null) {
            $browser->radio('decision', $keputusan);
        }

        if ($catatan !== null) {
            $this->isiTeks($browser, '@catatan-keputusan', $catatan);
        }

        $this->tandaiHalamanLama($browser);
        $browser->press('@simpan-keputusan-tinjau');

        return $this->tungguHalamanBaru($browser)->waitFor('@halaman-tinjau');
    }

    /** Kandidat login, menyerahkan Versi 1 lewat UI untuk setiap kesepakatan yang disebut, lalu Keluar. */
    protected function serahkanVersi1UntukKesepakatan(Browser $browser, array $kandidat, array $daftar): void
    {
        $this->masukSebagaiKandidat($browser, $kandidat);

        foreach ($daftar as $item) {
            $this->serahkanVersi1LewatUi(
                $browser,
                $item['judul'],
                $this->berkasUji('katalog_v1.pdf', 'pdf'),
                'https://figma.com/file/katalog-v1',
                $item['deskripsi'] ?? 'Katalog dan label versi pertama.'
            );
        }

        $this->keluar($browser);
    }

    /** UMKM login, memberi keputusan lewat halaman Tinjau Hasil, lalu Keluar. */
    protected function umkmMemutuskanHasilLewatUi(Browser $browser, array $kesepakatan, string $keputusan, ?string $catatan, int $versi): void
    {
        $this->masukSebagaiUmkm($browser);
        $this->bukaTinjauHasil($browser, $kesepakatan);
        $this->simpanKeputusanTinjau($browser, $keputusan, $catatan)
            ->assertSee($this->pesan($keputusan === 'approve' ? self::MSG_SETUJU : self::MSG_REVISI_TERKIRIM, ['v' => $versi]));
        $this->keluar($browser);
    }

    /** Kandidat (sudah login) mengirim versi perbaikan lewat UI di halaman Penyerahan Hasil. */
    protected function kirimVersiPerbaikanLewatUi(Browser $browser, string $judul, int $versi, ?string $berkas, ?string $tautan, string $deskripsi): void
    {
        $this->bukaPenyerahanHasil($browser, $judul)
            ->assertSeeIn('@judul-form-penyerahan', "Kirim Versi Perbaikan · Versi {$versi}");
        $this->isiFormPenyerahan($browser, $berkas, $tautan, $deskripsi);
        $this->kirimPenyerahan($browser)
            ->assertSee("Hasil pekerjaan Versi {$versi} berhasil diserahkan dan berstatus Menunggu Tinjauan.");
    }

    /** Request keputusan langsung dari halaman saat ini (seperti lewat DevTools), memakai helper existing. */
    protected function kirimKeputusanLangsung(Browser $browser, int $kesepakatanId, array $isian): Browser
    {
        return $this->kirimFormManipulasi($browser, 'POST', $this->urlTinjau($kesepakatanId), $isian);
    }

    protected function revisiDb(int $penyerahanId): array
    {
        return \Illuminate\Support\Facades\DB::table('revision_requests')->where('submission_id', $penyerahanId)->orderBy('id')->get()->all();
    }
}
