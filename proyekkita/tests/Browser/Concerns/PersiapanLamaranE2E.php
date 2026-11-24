<?php

namespace Tests\Browser\Concerns;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use Throwable;

/**
 * Persiapan precondition lamaran lewat UI untuk test E2E PBI-17.
 * Dipakai bersama AlurE2EProyekKita (helper dasar yang juga dipakai PBI-16; tidak diubah).
 *
 * Setiap helper dimulai TANPA sesi login, login lewat /login sesuai aktornya, dan diakhiri dengan Keluar.
 * Tidak ada factory, seeder, SQL tulis, loginAs(), atau actingAs(). Database hanya dibaca.
 */
trait PersiapanLamaranE2E
{
    /**
     * UMKM (sudah login) memastikan proyek yang baru diajukan tersimpan: kartu proyek tampil di Proyek Saya
     * dengan judul yang benar dan status "Menunggu Moderasi". Daftar diurutkan dari proyek terbaru.
     */
    protected function umkmMemastikanProyekTersimpan(Browser $browser, int $proyekId, string $judul): void
    {
        $browser->visit('/umkm/proyek')
            ->waitFor("@kartu-proyek-{$proyekId}")
            ->assertSeeIn("@kartu-proyek-{$proyekId}", $judul)
            ->assertSeeIn("@status-proyek-{$proyekId}", 'Menunggu Moderasi');
    }

    /** Kandidat login lewat /login, melamar setiap proyek lewat Eksplor Proyek sesuai urutan, lalu Keluar. */
    protected function kandidatMelamarLewatUi(Browser $browser, array $kandidat, array $daftarJudul): void
    {
        $this->masukSebagaiKandidat($browser, $kandidat);

        foreach ($daftarJudul as $judul) {
            $this->ajukanLamaran($browser, $judul)
                ->assertSeeIn('@status-lamaran', 'Lamaran Anda sudah terkirim');
        }

        $this->keluar($browser);
    }

    /**
     * UMKM login lewat /login, memberi keputusan di halaman Seleksi Kandidat, lalu Keluar.
     *
     * @param  list<array{0: int, 1: string, 2: ?string}>  $keputusan  [id proyek, shortlist|accept|reject, catatan]
     */
    protected function umkmMemutuskanLewatUi(Browser $browser, string $namaKandidat, array $keputusan): void
    {
        $this->masukSebagaiUmkm($browser);

        foreach ($keputusan as [$proyekId, $jenis, $catatan]) {
            $this->putuskanPelamar($browser, $proyekId, $namaKandidat, $jenis, $catatan);
        }

        $this->keluar($browser);
    }

    /** Kandidat login lewat /login, membatalkan lamaran lewat dialog di Lamaran Saya, lalu Keluar. */
    /**
     * Precondition "kandidat melamar lalu membatalkan" dalam SATU sesi kandidat: langkah dan assertion sama dengan
     * kandidatMelamarLewatUi() diikuti kandidatMembatalkanLewatUi(), tanpa logout/login di antara keduanya
     * (aktornya sama, sehingga logout/login tersebut tidak menambah isolasi sesi).
     */
    protected function kandidatMelamarLaluMembatalkanLewatUi(Browser $browser, array $kandidat, array $daftarJudul): void
    {
        $this->masukSebagaiKandidat($browser, $kandidat);

        foreach ($daftarJudul as $judul) {
            $this->ajukanLamaran($browser, $judul)
                ->assertSeeIn('@status-lamaran', 'Lamaran Anda sudah terkirim');
        }

        foreach ($daftarJudul as $judul) {
            $this->bukaLamaranSaya($browser);
            $this->batalkanLewatDialog($browser, $this->idLamaranDariKartu($browser, $judul));
        }

        $this->keluar($browser);
    }

    protected function kandidatMembatalkanLewatUi(Browser $browser, array $kandidat, array $daftarJudul): void
    {
        $this->masukSebagaiKandidat($browser, $kandidat);

        foreach ($daftarJudul as $judul) {
            $this->bukaLamaranSaya($browser);
            $this->batalkanLewatDialog($browser, $this->idLamaranDariKartu($browser, $judul));
        }

        $this->keluar($browser);
    }

    /** Label riwayat yang diharapkan, diturunkan dari data riwayat di database (urutan changed_at desc, id desc). */
    protected function riwayatDiharapkanDariDb(int $lamaranId): array
    {
        $baris = DB::table('application_status_histories')
            ->where('application_id', $lamaranId)
            ->orderByDesc('changed_at')
            ->orderByDesc('id')
            ->get();

        $label = [
            'pending' => 'Menunggu keputusan',
            'shortlisted' => 'Terpilih sementara',
            'accepted' => 'Diterima',
            'rejected' => 'Ditolak',
            'cancelled' => 'Dibatalkan',
        ];

        return $baris->values()->map(function ($item, $index) use ($baris, $label) {
            $awal = $index === $baris->count() - 1 && $item->status === 'pending';

            return [
                'label' => $awal ? 'Lamaran dikirim' : $label[$item->status],
                'tanggal' => $item->changed_at === null ? null : $this->tanggalIndonesia($item->changed_at, 'j M Y, H.i'),
            ];
        })->all();
    }

    /**
     * Jumlah statement SQL yang tercatat MySQL untuk schema database test (performance_schema, read-only).
     * Mengembalikan null bila performance_schema tidak aktif atau tidak dapat dibaca.
     */
    protected function jumlahStatementSchemaTest(): ?int
    {
        try {
            $aktif = (int) (DB::selectOne('SELECT @@performance_schema AS aktif')->aktif ?? 0);
            if ($aktif !== 1) {
                return null;
            }

            return (int) DB::selectOne(
                'SELECT COALESCE(SUM(COUNT_STAR), 0) AS jumlah FROM performance_schema.events_statements_summary_by_digest WHERE SCHEMA_NAME = ?',
                [DB::getDatabaseName()]
            )->jumlah;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Jumlah query satu kali memuat ulang halaman Lamaran Saya di browser (diambil nilai terkecil dari 3 kali muat
     * agar kebisingan dari koneksi lain tidak terbawa). Selisih termasuk 1 statement pengukur yang konstan.
     */
    protected function jumlahQueryMuatLamaranSaya(Browser $browser): ?int
    {
        $hasil = [];

        for ($i = 0; $i < 3; $i++) {
            $sebelum = $this->jumlahStatementSchemaTest();
            $this->tandaiHalamanLama($browser);
            $browser->refresh();
            $this->tungguHalamanBaru($browser)->waitFor('nav.la-tabs');
            $sesudah = $this->jumlahStatementSchemaTest();

            if ($sebelum === null || $sesudah === null) {
                return null;
            }

            $hasil[] = $sesudah - $sebelum;
        }

        return min($hasil);
    }
}
