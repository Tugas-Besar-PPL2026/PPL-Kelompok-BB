<?php

namespace Tests\Browser\Cerin;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use Laravel\Dusk\TestCase as BaseDuskTestCase;
use PHPUnit\Framework\SkippedTest;
use RuntimeException;
use Tests\DuskTestCase;
use Throwable;

/**
 * Base class Test Case Laravel Dusk milik Cerin (PBI-19, PBI-20, PBI-21).
 * File ini BUKAN Test Case (nama file tidak berakhiran Test.php).
 *
 * Setiap Test Case menyiapkan sendiri seluruh data skenarionya MELALUI UI BROWSER:
 *   daftar akun -> lengkapi profil -> (Admin) verifikasi UMKM -> buat & ajukan proyek -> (Admin) setujui proyek
 *   -> kandidat lengkapi profil & melamar -> UMKM memberi keputusan / kandidat membatalkan -> skenario utama.
 *
 * Tidak dipakai: factory, faker, seeder, actingAs(), loginAs(), Auth::login(), manipulasi session/cookie,
 * migrate / migrate:fresh / db:wipe / truncate, maupun insert/update/delete lewat SQL/Eloquent.
 * Satu-satunya akun yang harus sudah ada adalah Admin (aplikasi tidak menyediakan pendaftaran admin).
 *
 * Nama data dibuat unik tanpa nilai acak: kode Test Case + cap waktu saat test dijalankan.
 */
abstract class CerinDuskTestCase extends DuskTestCase
{
    /** Halaman tujuan setelah login berhasil (User::homeRoute()). */
    private const HALAMAN_AWAL = [
        'umkm' => '/umkm/profil',
        'kandidat' => '/kandidat/profil',
        'admin' => '/admin/data-master',
    ];

    /** Kode Test Case, mis. TC-PBI19-001 (diambil dari nama class). */
    protected string $kodeTc;

    /** Cap waktu unik untuk satu kali eksekusi test, mis. 20261117031502. */
    protected string $stempel;

    protected function setUp(): void
    {
        // DuskTestCase milik kelompok menjalankan "migrate --force"; test Cerin tidak mengubah struktur database.
        BaseDuskTestCase::setUp();

        $this->pastikanDatabaseDusk();

        preg_match('/TC_PBI(\d+)_(\d+)_Test$/', static::class, $m);
        $this->kodeTc = isset($m[1]) ? "TC-PBI{$m[1]}-{$m[2]}" : class_basename(static::class);
        $this->stempel = now()->format('YmdHis');
    }

    /* =========================================================================================
     |  Pengaman database & konfigurasi
     * ========================================================================================= */

    private function pastikanDatabaseDusk(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('Test Dusk tidak boleh dijalankan di environment production.');
        }

        $nama = (string) DB::connection()->getDatabaseName();

        if (! str_contains(strtolower(basename($nama)), 'dusk')) {
            throw new RuntimeException(
                "Database aktif \"{$nama}\" bukan database khusus Dusk (nama harus mengandung kata 'dusk'). "
                .'Test dihentikan. Periksa .env.dusk.local dan pastikan MySQL aktif.'
            );
        }
    }

    /** Nilai wajib dari .env.dusk.local. Kosong -> test Blocked. */
    protected function butuh(string $kunci): string
    {
        $nilai = env($kunci);

        if ($nilai === null || trim((string) $nilai) === '') {
            $this->markTestSkipped("BLOCKED: variabel {$kunci} belum diisi di .env.dusk.local.");
        }

        return (string) $nilai;
    }

    /** Hentikan test dengan status Blocked beserta alasannya. */
    protected function blocked(string $alasan): never
    {
        $this->markTestSkipped("BLOCKED: {$alasan}");
    }

    /**
     * Jalankan satu langkah persiapan data. Bila gagal, test dihentikan dengan pesan "SETUP GAGAL"
     * sehingga jelas bahwa kegagalan terjadi sebelum skenario utama.
     */
    protected function persiapan(string $langkah, callable $aksi): mixed
    {
        try {
            return $aksi();
        } catch (SkippedTest $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new RuntimeException("SETUP GAGAL ({$this->kodeTc} - {$langkah}): ".$e->getMessage(), 0, $e);
        }
    }

    /** Akun Admin yang sudah tersedia (dibuat manual sesuai panduan kelompok 12.2). */
    protected function akunAdmin(): array
    {
        return [
            'email' => $this->butuh('DUSK_ADMIN_EMAIL'),
            'password' => $this->butuh('DUSK_ADMIN_PASSWORD'),
            'peran' => 'admin',
            'nama' => 'Admin',
        ];
    }

    /** Kata sandi semua akun yang didaftarkan oleh test. */
    protected function kataSandiUji(): string
    {
        return $this->butuh('DUSK_PASSWORD_UJI');
    }

    private function slug(string $label): string
    {
        return strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $label));
    }

    /* =========================================================================================
     |  Login & logout lewat UI
     * ========================================================================================= */

    /** Buka /login, isi email & kata sandi, klik Masuk, lalu pastikan diarahkan ke halaman awal sesuai peran. */
    protected function masuk(Browser $browser, array $akun): Browser
    {
        // Mulai dari kondisi belum login: hapus cookie sesi browser sebelumnya.
        $browser->visit('/login');
        $browser->driver->manage()->deleteAllCookies();

        return $browser->visit('/login')
            ->assertPathIs('/login')
            ->type('#email', $akun['email'])
            ->type('#password', $akun['password'])
            ->click('@masuk')
            ->waitForLocation(self::HALAMAN_AWAL[$akun['peran']], 20)
            ->assertPathIs(self::HALAMAN_AWAL[$akun['peran']]);
    }

    /** Klik Keluar di menu samping. */
    protected function keluar(Browser $browser): Browser
    {
        return $browser->press('Keluar')->waitForLocation('/', 20)->assertPathIs('/');
    }

    /* =========================================================================================
     |  Persiapan data: akun UMKM
     * ========================================================================================= */

    /**
     * Daftar akun UMKM lewat halaman pendaftaran, lalu lengkapi Profil Usaha. Diakhiri logout.
     *
     * @return array{email: string, password: string, peran: string, nama: string, usaha: string}
     */
    protected function daftarUmkm(Browser $browser, string $label): array
    {
        return $this->persiapan("daftar UMKM {$label}", function () use ($browser, $label) {
            $akun = [
                'email' => "{$this->slug($this->kodeTc)}.{$this->slug($label)}.{$this->stempel}@dusk.proyekkita.test",
                'password' => $this->kataSandiUji(),
                'peran' => 'umkm',
                'nama' => "Pemilik {$label} {$this->kodeTc}",
                'usaha' => "Usaha {$label} {$this->kodeTc} {$this->stempel}",
            ];

            $browser->visit('/');
            $browser->driver->manage()->deleteAllCookies();
            $browser->visit('/')
                ->click('@hero-daftar-umkm')
                ->waitForLocation('/daftar/umkm', 20);
            $this->isiFormDaftar($browser, $akun, '/umkm/profil');

            $browser->type('@nama-usaha', $akun['usaha'])
                ->type('@bidang-usaha', 'Jasa')
                ->type('@deskripsi-usaha', "Usaha uji otomatis untuk {$this->kodeTc}.")
                ->type('@kontak-usaha', '081234567890')
                ->type('@kota-usaha', 'Bandung')
                ->type('@alamat-usaha', 'Jl. Pengujian No. 1, Bandung')
                ->type('@nib', '9120001234567')
                ->click('@simpan-profil')
                ->waitForText('Profil usaha berhasil disimpan.', 20)
                ->assertSeeIn('@status-verifikasi', 'Menunggu');

            $this->keluar($browser);

            return $akun;
        });
    }

    /** Isi form pendaftaran (nama, email, kata sandi, syarat) lalu klik Daftar. */
    private function isiFormDaftar(Browser $browser, array $akun, string $tujuan): void
    {
        $browser->type('#name', $akun['nama'])
            ->type('#email', $akun['email'])
            ->type('#password', $akun['password'])
            ->type('#password_confirmation', $akun['password'])
            ->check('#terms')
            ->click('@daftar')
            ->waitForLocation($tujuan, 20)
            ->assertSee('Akun berhasil dibuat');
    }

    /** Admin memverifikasi UMKM lewat menu Verifikasi UMKM. Diakhiri logout. */
    protected function verifikasiUmkm(Browser $browser, array $umkm): void
    {
        $this->persiapan("verifikasi {$umkm['usaha']}", function () use ($browser, $umkm) {
            $this->masuk($browser, $this->akunAdmin());
            $browser->clickLink('Verifikasi UMKM')
                ->waitForLocation('/admin/verifikasi', 20)
                ->click('@antrean-pending')
                ->waitForText($umkm['usaha'], 20)
                ->clickLink($umkm['usaha'])
                ->waitFor('@detail-usaha', 20)
                ->assertSeeIn('@detail-usaha', $umkm['usaha'])
                ->press('@btn-setujui')
                ->waitForText('Akun UMKM berhasil diverifikasi.', 20);
            $this->keluar($browser);
        });
    }

    /** Daftar UMKM + Admin memverifikasinya. */
    protected function siapkanUmkmTerverifikasi(Browser $browser, string $label): array
    {
        $umkm = $this->daftarUmkm($browser, $label);
        $this->verifikasiUmkm($browser, $umkm);

        return $umkm;
    }

    /* =========================================================================================
     |  Persiapan data: proyek
     * ========================================================================================= */

    /**
     * UMKM membuat proyek lewat form Buat Proyek lalu mengajukannya ke moderasi. Diakhiri logout.
     * Kategori dan keterampilan memakai data master aktif pertama yang tampil di form.
     *
     * @return array{id: int, judul: string, kuota: int, keterampilan: string}
     */
    protected function buatProyek(Browser $browser, array $umkm, string $label, int $kuota): array
    {
        return $this->persiapan("buat proyek {$label}", function () use ($browser, $umkm, $label, $kuota) {
            $judul = "{$this->kodeTc} {$label} {$this->stempel}";

            $this->masuk($browser, $umkm);
            $browser->clickLink('Proyek Saya')
                ->waitFor('@buat-proyek', 20)
                ->click('@buat-proyek')
                ->waitForLocation('/umkm/proyek/buat', 20);

            $kategori = $browser->script("return [...document.querySelectorAll('#category_id option')].map(o => o.value).filter(v => v !== '')[0] || null;")[0];
            $this->assertNotNull($kategori, 'Tidak ada kategori aktif pada data master.');
            $keterampilan = $browser->script("const l = document.querySelector('label.pick[dusk^=\"skill-\"]'); return l ? {dusk: l.getAttribute('dusk'), nama: l.innerText.trim()} : null;")[0];
            $this->assertNotNull($keterampilan, 'Tidak ada keterampilan aktif pada data master.');

            $browser->type('title', $judul)
                ->select('category_id', (string) $kategori)
                ->click('@'.$keterampilan['dusk'])
                ->type('description', "Proyek uji otomatis {$this->kodeTc} untuk pengujian seleksi kandidat.")
                ->type('expected_output', "Hasil pekerjaan uji {$this->kodeTc}");

            // Kolom tanggal diisi lewat JavaScript karena format ketik tanggal berbeda menurut bahasa browser.
            $tenggat = now()->addDays(30)->toDateString();
            $target = now()->addDays(60)->toDateString();
            $browser->script([
                "document.getElementById('application_deadline').value = '{$tenggat}';",
                "document.getElementById('target_completion_date').value = '{$target}';",
            ]);

            $browser->clear('quota')->type('quota', (string) $kuota)
                ->type('total_compensation', '1000000')
                ->click('@cara-sekali')
                ->type('#tabel-termin tbody tr:nth-child(1) [name$="[condition]"]', 'Dibayar setelah pekerjaan selesai');

            $browser->script('window.scrollTo(0, 0);');
            $browser->click('@tinjau-ajukan')
                ->waitForText('Syarat Pengajuan', 20)
                ->check('@pernyataan')
                ->click('@ajukan-moderasi')
                ->waitForText('Proyek diajukan dan menunggu moderasi admin.', 20);

            preg_match('#/umkm/proyek/(\d+)/tinjau#', (string) $browser->driver->getCurrentURL(), $m);
            $this->assertNotEmpty($m[1] ?? null, 'ID proyek tidak ditemukan pada URL halaman tinjau.');

            $this->keluar($browser);

            return ['id' => (int) $m[1], 'judul' => $judul, 'kuota' => $kuota, 'keterampilan' => $keterampilan['nama']];
        });
    }

    /** Admin menyetujui proyek lewat menu Moderasi Proyek. Diakhiri logout. */
    protected function setujuiProyek(Browser $browser, array $proyek): void
    {
        $this->persiapan("setujui proyek {$proyek['judul']}", function () use ($browser, $proyek) {
            $this->masuk($browser, $this->akunAdmin());
            $browser->clickLink('Moderasi Proyek')
                ->waitForLocation('/admin/moderasi-proyek', 20)
                ->waitForText($proyek['judul'], 20)
                ->clickLink($proyek['judul'])
                ->waitForText('Simpan Keputusan', 20)
                ->radio('decision', 'approved')
                ->press('Simpan Keputusan')
                ->waitForText('Keputusan moderasi proyek berhasil disimpan.', 20);

            // Proyek kini berada pada tab Dipublikasikan
            $browser->visit('/admin/moderasi-proyek?status=published')
                ->waitForText($proyek['judul'], 20);
            $this->keluar($browser);
        });
    }

    /** Buat proyek lalu Admin menyetujuinya sehingga berstatus dipublikasikan. */
    protected function siapkanProyekTerbit(Browser $browser, array $umkm, string $label, int $kuota): array
    {
        $proyek = $this->buatProyek($browser, $umkm, $label, $kuota);
        $this->setujuiProyek($browser, $proyek);

        return $proyek;
    }

    /* =========================================================================================
     |  Persiapan data: kandidat & lamaran
     * ========================================================================================= */

    /**
     * Daftar kandidat, isi Data Diri, tambah keterampilan, dan (opsional) pengalaman & portofolio. Diakhiri logout.
     *
     * $opsi:
     *  - keterampilan (string|null): nama keterampilan yang ditambahkan; null = keterampilan pertama yang tersedia
     *  - pengalaman (bool): tambah satu pengalaman kerja
     *  - portofolio (list<array{judul: string, tautan?: string, berkas?: bool}>)
     *
     * @return array{email: string, password: string, peran: string, nama: string, data: array}
     */
    protected function daftarKandidat(Browser $browser, string $label, array $opsi = []): array
    {
        return $this->persiapan("daftar kandidat {$label}", function () use ($browser, $label, $opsi) {
            $data = [
                'full_name' => "Kandidat {$label} {$this->kodeTc} {$this->stempel}",
                'city' => 'Kota Bandung',
                'headline' => "Desainer grafis {$label}",
                'university' => 'Universitas Pengujian Indonesia',
                'major' => 'Desain Komunikasi Visual',
                'summary' => "Ringkasan diri kandidat {$label} untuk pengujian {$this->kodeTc}.",
            ];
            $akun = [
                'email' => "{$this->slug($this->kodeTc)}.{$this->slug($label)}.{$this->stempel}@dusk.proyekkita.test",
                'password' => $this->kataSandiUji(),
                'peran' => 'kandidat',
                'nama' => $data['full_name'],
            ];

            $browser->visit('/');
            $browser->driver->manage()->deleteAllCookies();
            $browser->visit('/')
                ->click('@hero-daftar-kandidat')
                ->waitForLocation('/daftar/kandidat', 20);
            $this->isiFormDaftar($browser, $akun, '/kandidat/profil');

            // Data Diri
            $browser->click('a[href$="/kandidat/profil/data-diri"]')
                ->waitForLocation('/kandidat/profil/data-diri', 20)
                ->type('@nama-lengkap', $data['full_name'])
                ->type('@domisili', $data['city'])
                ->type('@headline', $data['headline'])
                ->type('@universitas', $data['university'])
                ->type('@program-studi', $data['major'])
                ->type('@ringkasan', $data['summary'])
                ->click('@simpan-profil')
                ->waitForText('Data diri berhasil disimpan.', 20);

            // Keterampilan (wajib minimal satu agar bisa melamar)
            $browser->visit('/kandidat/profil')->waitFor('#skill-list', 20);
            $nama = $opsi['keterampilan'] ?? null;
            $dipilih = $browser->script(sprintf(
                "const n = %s;
                 const baris = [...document.querySelectorAll('[data-skill-row]')].find(r => r.querySelector('.skill-add') && (n === null || r.dataset.skillName === n.toLowerCase()));
                 if (!baris) { return null; }
                 return baris.querySelector('span').innerText.trim();",
                json_encode($nama)
            ))[0];
            $this->assertNotNull($dipilih, 'Keterampilan '.($nama ?? 'aktif').' tidak tersedia di halaman profil kandidat.');
            $browser->waitForReload(fn (Browser $b) => $b->script(sprintf(
                "[...document.querySelectorAll('[data-skill-row]')].find(r => r.dataset.skillName === %s).querySelector('.skill-add').click();",
                json_encode(mb_strtolower($dipilih))
            )), 20)->assertSee('Keterampilan berhasil ditambahkan.');
            $data['keterampilan'] = $dipilih;

            if ($opsi['pengalaman'] ?? false) {
                $data['pengalaman'] = ['work_type' => 'Desain Grafis Freelance', 'duration' => '6 bulan'];
                $browser->type('#work_type', $data['pengalaman']['work_type'])
                    ->type('#duration', $data['pengalaman']['duration'])
                    ->type('#exp_description', 'Membuat materi promosi untuk UMKM.')
                    ->click('#btn-submit-exp')
                    ->waitForText('Pengalaman kerja berhasil ditambahkan.', 20);
            }

            foreach ($opsi['portofolio'] ?? [] as $pf) {
                $browser->visit('/kandidat/profil')
                    ->click('a[href$="/kandidat/profil/portofolio"]')
                    ->waitForLocation('/kandidat/profil/portofolio', 20)
                    ->type('#pf_title', $pf['judul']);
                if (! empty($pf['tautan'])) {
                    $browser->type('#pf_link', $pf['tautan']);
                }
                if (! empty($pf['berkas'])) {
                    $browser->attach('#pf_file', __DIR__.'/fixtures/cv.pdf');
                }
                $browser->click('#btn-submit-pf')->waitForText('Portofolio berhasil ditambahkan.', 20);
            }
            $data['portofolio'] = array_column($opsi['portofolio'] ?? [], 'judul');

            $this->keluar($browser);
            $akun['data'] = $data;

            return $akun;
        });
    }

    /** Pesan lamaran (minimal 50 karakter sesuai ApplicationRequest). */
    protected function pesanLamaran(string $labelKandidat): string
    {
        return "Saya {$labelKandidat} tertarik mengerjakan proyek ini dan siap berkolaborasi sesuai jadwal ({$this->kodeTc}).";
    }

    /** Kandidat melamar proyek lewat Eksplor Proyek -> detail proyek -> Kirim Lamaran. Diakhiri logout. */
    protected function lamar(Browser $browser, array $kandidat, array $proyek, ?string $pesan = null): void
    {
        $this->persiapan("{$kandidat['nama']} melamar {$proyek['judul']}", function () use ($browser, $kandidat, $proyek, $pesan) {
            $this->masuk($browser, $kandidat);
            $browser->clickLink('Eksplor Proyek')
                ->waitForLocation('/kandidat/proyek', 20)
                ->type('@cari-proyek', $proyek['judul']);
            $browser->waitForReload(fn (Browser $b) => $b->click('@terapkan-filter'), 20)
                ->waitForText($proyek['judul'], 20)
                ->clickLink($proyek['judul'])
                ->waitForLocation("/kandidat/proyek/{$proyek['id']}", 20)
                ->type('@pesan-lamaran', $pesan ?? $this->pesanLamaran($kandidat['nama']));
            $browser->waitForReload(fn (Browser $b) => $b->click('@kirim-lamaran'), 20)
                ->assertSee('Lamaran berhasil dikirim dan berstatus Menunggu Keputusan.');
            $this->keluar($browser);
        });
    }

    /**
     * UMKM memberi keputusan lewat panel Keputusan Seleksi. $keputusan: shortlist | accept | reject. Diakhiri logout.
     *
     * @return int ID lamaran
     */
    protected function putuskan(Browser $browser, array $umkm, array $proyek, array $kandidat, string $keputusan, string $catatan = ''): int
    {
        return $this->persiapan("keputusan {$keputusan} untuk {$kandidat['nama']}", function () use ($browser, $umkm, $proyek, $kandidat, $keputusan, $catatan) {
            $this->masuk($browser, $umkm);
            $this->bukaSeleksi($browser);
            $this->pilihProyek($browser, $proyek['id']);
            $id = $this->lamaranIdDiTabel($browser, $kandidat['nama']);
            $this->lihatPelamar($browser, $id);
            $this->pilihKeputusan($browser, $keputusan);
            $this->isiCatatan($browser, $catatan);
            $this->simpanKeputusan($browser);
            $browser->assertSee(match ($keputusan) {
                'shortlist' => 'Pelamar dimasukkan ke daftar terpilih sementara.',
                'accept' => 'Pelamar berhasil diterima.',
                'reject' => 'Pelamar ditolak dan catatan penolakan tersimpan.',
            });
            $this->keluar($browser);

            return $id;
        });
    }

    /** Kandidat membatalkan lamarannya lewat Lamaran Saya. Diakhiri logout. */
    protected function batalkanSebagaiKandidat(Browser $browser, array $kandidat, array $proyek): void
    {
        $this->persiapan("{$kandidat['nama']} membatalkan lamaran", function () use ($browser, $kandidat, $proyek) {
            $this->masuk($browser, $kandidat);
            $this->bukaLamaranSaya($browser);
            $this->batalkanLamaran($browser, $this->lamaranIdDiLamaranSaya($browser, $proyek['judul']));
            $this->keluar($browser);
        });
    }

    /* =========================================================================================
     |  Langkah skenario utama
     * ========================================================================================= */

    protected function bukaSeleksi(Browser $browser): Browser
    {
        return $browser->clickLink('Seleksi Kandidat')
            ->waitForLocation('/umkm/seleksi', 20)
            ->assertPathIs('/umkm/seleksi');
    }

    protected function pilihProyek(Browser $browser, int $proyekId): Browser
    {
        $browser->waitFor('#pilih-proyek', 20);

        if ((string) $browser->value('#pilih-proyek') !== (string) $proyekId) {
            $browser->waitForReload(fn (Browser $b) => $b->select('#pilih-proyek', (string) $proyekId), 20);
        }

        return $browser->assertSelected('#pilih-proyek', (string) $proyekId);
    }

    /** ID lamaran dari tombol Lihat pada baris tabel yang memuat nama kandidat. */
    protected function lamaranIdDiTabel(Browser $browser, string $nama): int
    {
        $id = $browser->script(sprintf(
            "const n = %s;
             const tr = [...document.querySelectorAll('table.t.pl tbody tr')].find(r => (r.querySelector('.nm')?.innerText || '').trim() === n);
             const a = tr ? tr.querySelector('[dusk^=\"lihat-pelamar-\"]') : null;
             return a ? a.getAttribute('dusk').replace('lihat-pelamar-', '') : null;",
            json_encode($nama)
        ))[0];
        $this->assertNotNull($id, "Pelamar {$nama} tidak ditemukan pada daftar pelamar.");

        return (int) $id;
    }

    /** ID lamaran dari kartu Lamaran Saya yang memuat judul proyek. */
    protected function lamaranIdDiLamaranSaya(Browser $browser, string $judulProyek): int
    {
        $id = $browser->script(sprintf(
            "const j = %s;
             const c = [...document.querySelectorAll('article.la-card')].find(k => (k.querySelector('.la-title')?.innerText || '').trim() === j);
             return c ? c.id.replace('lamaran-', '') : null;",
            json_encode($judulProyek)
        ))[0];
        $this->assertNotNull($id, "Lamaran untuk proyek {$judulProyek} tidak ditemukan di Lamaran Saya.");

        return (int) $id;
    }

    protected function lihatPelamar(Browser $browser, int $lamaranId): Browser
    {
        return $browser->waitFor("@lihat-pelamar-{$lamaranId}", 20)
            ->click("@lihat-pelamar-{$lamaranId}")
            ->waitForLocation("/umkm/seleksi/pelamar/{$lamaranId}", 20)
            ->assertSee('Detail Pelamar');
    }

    protected function kembaliKeDaftar(Browser $browser): Browser
    {
        return $browser->clickLink('Kembali ke Daftar')->waitForLocation('/umkm/seleksi', 20);
    }

    /** $keputusan: shortlist | accept | reject (yang diklik adalah label pilihannya). */
    protected function pilihKeputusan(Browser $browser, string $keputusan): Browser
    {
        return $browser->waitFor("@opsi-{$keputusan}", 20)->click("@opsi-{$keputusan}");
    }

    protected function isiCatatan(Browser $browser, string $catatan): Browser
    {
        $browser->clear('@catatan-seleksi');

        if ($catatan !== '') {
            $browser->type('@catatan-seleksi', $catatan);
        }

        return $browser;
    }

    protected function simpanKeputusan(Browser $browser): Browser
    {
        return $browser->waitForReload(fn (Browser $b) => $b->click('@simpan-keputusan'), 20);
    }

    protected function bukaLamaranSaya(Browser $browser): Browser
    {
        return $browser->clickLink('Lamaran Saya')->waitForLocation('/kandidat/lamaran', 20);
    }

    protected function batalkanLamaran(Browser $browser, int $lamaranId): Browser
    {
        $browser->waitFor("@batalkan-lamaran-{$lamaranId}", 20)
            ->click("@batalkan-lamaran-{$lamaranId}")
            ->waitFor("@konfirmasi-batal-{$lamaranId}", 20);
        $browser->waitForReload(fn (Browser $b) => $b->click("@konfirmasi-batal-{$lamaranId}"), 20);

        return $browser->assertSeeIn("#lamaran-{$lamaranId} .la-meta .bd", 'Dibatalkan');
    }

    /** [Total, Menunggu, Terpilih Sementara, Diterima "x / y"] pada kartu statistik. */
    protected function statistik(Browser $browser): array
    {
        return $browser->script("return [...document.querySelectorAll('.stats .stat .v')].map(e => e.innerText.trim());")[0];
    }

    /** Teks tab status, mis. "Semua 5". */
    protected function tabStatus(Browser $browser): array
    {
        return $browser->script("return [...document.querySelectorAll('[dusk=\"tab-status\"] a')].map(a => a.innerText.replace(/\\s+/g, ' ').trim());")[0];
    }

    protected function namaDiTabel(Browser $browser): array
    {
        return $browser->script("return [...document.querySelectorAll('table.t.pl tbody tr .nm')].map(e => e.innerText.trim());")[0];
    }

    protected function badgeDiTabel(Browser $browser): array
    {
        return $browser->script("return [...document.querySelectorAll('table.t.pl tbody tr td span.bd')].map(e => e.innerText.trim());")[0];
    }

    /** @return array{ada: bool, teks: string, tautan: list<string>} */
    protected function kartuPortofolio(Browser $browser, string $judul): array
    {
        $h = $browser->script(sprintf(
            "const j = %s;
             const pf = [...document.querySelectorAll('.pfg .pf')].find(p => (p.querySelector('.nm')?.innerText || '').trim() === j);
             if (!pf) { return {ada: false, teks: '', tautan: []}; }
             return {ada: true, teks: pf.innerText.replace(/\\s+/g, ' ').trim(), tautan: [...pf.querySelectorAll('a')].map(a => a.getAttribute('href'))};",
            json_encode($judul)
        ))[0];

        return ['ada' => (bool) $h['ada'], 'teks' => (string) $h['teks'], 'tautan' => array_values($h['tautan'])];
    }

    /** Screenshot bukti di tests/Browser/screenshots/. */
    protected function bukti(Browser $browser, string $nama): void
    {
        $browser->screenshot('Cerin_'.$nama);
    }
}
