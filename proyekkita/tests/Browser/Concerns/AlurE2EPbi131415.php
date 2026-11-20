<?php

namespace Tests\Browser\Concerns;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverSelect;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use RuntimeException;

/**
 * Helper End-to-End (E2E) untuk PBI-13 (Proyek Saya), PBI-14 (Eksplor & Detail Proyek), dan PBI-15 (Cari & Filter).
 * Dipakai bersama AlurE2EProyekKita (login, keluar, registrasi, lamaran, pembatalan).
 *
 * Prinsip sama dengan AlurE2EProyekKita:
 *  1. Semua data skenario dibuat lewat UI di Chrome: master data, registrasi, profil usaha, verifikasi UMKM,
 *     form proyek, pengajuan, moderasi, lamaran, dan pembatalan. Tanpa factory, seeder, SQL INSERT, atau loginAs().
 *  2. Database hanya DIBACA: untuk verifikasi tambahan dan untuk mencari data bersama yang bisa dipakai ulang.
 *  3. Database persistent: setiap test memakai kode unik, sehingga dapat dijalankan berulang kali.
 *  4. Data uji mengikuti kolom Input dan Precondition di "PBI 13 14 15.xlsx".
 */
trait AlurE2EPbi131415
{
    // ------------------------------------------------------------------
    // Data master sesuai Precondition Excel
    // ------------------------------------------------------------------

    protected const KAT_DESAIN = 'Desain Grafis & Branding';

    protected const KAT_PEMASARAN = 'Pemasaran Digital';

    protected const KET_FIGMA = 'Figma';

    protected const KET_KEMASAN = 'Desain Kemasan';

    protected const KET_COPYWRITING = 'Copywriting';

    protected const SANDI_UJI = 'Password123!';

    protected const MSG_DRAF_DISIMPAN = 'Draf proyek disimpan.';

    protected const MSG_DIAJUKAN = 'Proyek diajukan dan menunggu moderasi admin.';

    protected const MSG_MODERASI_DISIMPAN = 'Keputusan moderasi proyek berhasil disimpan.';

    /** UMKM bersama untuk katalog PBI-15 (dipakai ulang antar-run). */
    protected const UMKM_PBI15 = 'Kopi Nusantara PBI15';

    private static bool $masterDataExcelSiap = false;

    // ------------------------------------------------------------------
    // Master data (Admin) - dibuat lewat halaman Data Master bila belum ada
    // ------------------------------------------------------------------

    protected function pastikanMasterDataExcel(Browser $browser): void
    {
        if (self::$masterDataExcelSiap) {
            return;
        }

        $kategoriKurang = array_values(array_filter(
            [self::KAT_DESAIN, self::KAT_PEMASARAN],
            fn (string $nama) => ! DB::table('categories')->where('name', $nama)->exists()
        ));
        $keterampilanKurang = array_values(array_filter(
            [self::KET_FIGMA, self::KET_KEMASAN, self::KET_COPYWRITING],
            fn (string $nama) => ! DB::table('skills')->where('name', $nama)->exists()
        ));

        if ($kategoriKurang !== [] || $keterampilanKurang !== []) {
            $this->masukSebagaiAdmin($browser);
            $browser->visit('/admin/data-master')->waitFor('@kartu-keterampilan');

            foreach ($kategoriKurang as $nama) {
                $browser->type('form[action$="/admin/data-master/kategori"] input[name="name"]', $nama)
                    ->click('form[action$="/admin/data-master/kategori"] button[type="submit"]')
                    ->waitForText($nama);
            }

            foreach ($keterampilanKurang as $nama) {
                $browser->type('@input-keterampilan-baru', $nama)
                    ->press('@tambah-keterampilan')
                    ->waitForText('Keterampilan baru berhasil ditambahkan.');
            }

            $this->keluar($browser);
        }

        foreach (['categories' => [self::KAT_DESAIN, self::KAT_PEMASARAN], 'skills' => [self::KET_FIGMA, self::KET_KEMASAN, self::KET_COPYWRITING]] as $tabel => $daftar) {
            foreach ($daftar as $nama) {
                if (! DB::table($tabel)->where('name', $nama)->where('is_active', true)->exists()) {
                    throw new RuntimeException("Data master \"{$nama}\" ada tetapi nonaktif. Aktifkan lewat halaman Data Master Admin.");
                }
            }
        }

        self::$masterDataExcelSiap = true;
    }

    protected function idKategori(string $nama): int
    {
        return (int) DB::table('categories')->where('name', $nama)->value('id');
    }

    // ------------------------------------------------------------------
    // UMKM
    // ------------------------------------------------------------------

    /** Profil usaha sesuai Input TC-PBI13-001. */
    protected function profilUniqlo(): array
    {
        return [
            'nama' => 'Uniqlo',
            'bidang' => 'Fashion',
            'deskripsi' => 'Uniqlo menyediakan pakaian.',
            'kontak' => '0812-3456-7801',
            'kota' => 'Bandung',
            'alamat' => 'Jl. Cihampelas No. 112 Bandung',
            'nib' => '1234567890123',
        ];
    }

    /** Profil usaha UMKM penyelenggara proyek uji PBI-14. [kode] diganti kode unik saat registrasi. */
    protected function profilWarungUji(): array
    {
        return [
            'nama' => 'Warung Uji PBI14 [kode]',
            'bidang' => 'Kuliner & Minuman',
            'deskripsi' => 'Warung kopi yang menjual biji kopi kemasan.',
            'kontak' => '0812-3456-7814',
            'kota' => 'Bandung',
            'alamat' => 'Jl. Dago No. 14 Bandung',
            'nib' => '1234567890114',
        ];
    }

    /** Data proyek sesuai Input TC-PBI14-001. Hasil pekerjaan ditulis dengan tanda poin di awal baris. */
    protected function dataProyekPbi14(string $kode, array $timpa = []): array
    {
        return $this->dataProyek(array_merge([
            'judul' => "Redesain Label Kemasan {$kode}",
            'kategori' => self::KAT_DESAIN,
            'keterampilan' => [self::KET_KEMASAN, self::KET_FIGMA],
            'deskripsi' => 'Warung Uji membutuhkan desainer untuk memperbarui label kemasan tiga varian biji kopi.',
            'hasil' => "• Desain label kemasan 3 varian siap cetak\n• Katalog produk digital 8 halaman\n• File sumber Figma",
            'tenggat_hari' => 7,
            'target_hari' => 30,
            'kuota' => 2,
            'kompensasi' => 2500000,
            'termin' => [[2500000, 'Dibayar penuh setelah file desain final diserahkan dan disetujui']],
        ], $timpa));
    }

    /** Registrasi UMKM baru lewat /daftar/umkm. Setelah registrasi pengguna sudah login. */
    protected function daftarUmkmBaru(Browser $browser, string $label): array
    {
        $kode = $this->kodeUnik();
        $akun = [
            'nama' => "Penanggung Jawab {$label}",
            'email' => 'dusk.umkm.'.Str::lower($label).'.'.$kode.'@proyekkita.test',
            'sandi' => self::SANDI_UJI,
            'kode' => $kode,
        ];

        $this->daftar($browser, 'umkm', $akun['nama'], $akun['email'], $akun['sandi']);

        return $akun;
    }

    /** UMKM (sudah login) mengisi form Profil Usaha lalu klik Simpan Perubahan. */
    protected function isiProfilUsaha(Browser $browser, array $profil): Browser
    {
        return $browser->visit('/umkm/profil')
            ->waitFor('@nama-usaha')
            ->type('@nama-usaha', $profil['nama'])
            ->type('@bidang-usaha', $profil['bidang'])
            ->type('@deskripsi-usaha', $profil['deskripsi'])
            ->type('@kontak-usaha', $profil['kontak'])
            ->type('@kota-usaha', $profil['kota'])
            ->type('@alamat-usaha', $profil['alamat'])
            ->type('@nib', $profil['nib'])
            ->press('@simpan-profil')
            ->waitForText('Profil usaha berhasil disimpan.');
    }

    protected function idProfilUmkm(string $email): int
    {
        return (int) DB::table('umkm_profiles')
            ->join('users', 'users.id', '=', 'umkm_profiles.user_id')
            ->where('users.email', mb_strtolower($email))
            ->value('umkm_profiles.id');
    }

    /** Admin memverifikasi UMKM lewat menu Verifikasi UMKM. Dimulai dan diakhiri tanpa sesi login. */
    protected function verifikasiUmkm(Browser $browser, string $email): void
    {
        $idProfil = $this->idProfilUmkm($email);

        $this->masukSebagaiAdmin($browser);
        $browser->visit('/admin/verifikasi?status=pending')
            ->waitFor("@item-usaha-{$idProfil}")
            ->click("@item-usaha-{$idProfil}")
            ->waitFor('@detail-usaha')
            ->assertSeeIn('@detail-pemilik', mb_strtolower($email))
            ->press('@btn-setujui')
            ->waitForText('Akun UMKM berhasil diverifikasi.');
        $this->keluar($browser);
    }

    /**
     * UMKM baru: registrasi, isi profil usaha, lalu (opsional) diverifikasi Admin. Diakhiri tanpa sesi login.
     */
    protected function siapkanUmkmBaru(Browser $browser, string $label, array $profil, bool $verifikasi = true): array
    {
        $this->pastikanMasterDataExcel($browser);

        $akun = $this->daftarUmkmBaru($browser, $label);
        $profil['nama'] = str_replace('[kode]', $akun['kode'], $profil['nama']);
        $this->isiProfilUsaha($browser, $profil);
        $this->keluar($browser);

        if ($verifikasi) {
            $this->verifikasiUmkm($browser, $akun['email']);
        }

        return $akun + ['profil' => $profil];
    }

    // ------------------------------------------------------------------
    // Proyek
    // ------------------------------------------------------------------

    /**
     * Data proyek bawaan sesuai Input TC-PBI13-002. Kunci yang dapat ditimpa:
     * judul, kategori, keterampilan, deskripsi, hasil, tenggat_hari, target_hari, kuota, kompensasi, termin.
     * termin: list of [nominal, kondisi]. Satu termin = Sekali bayar, lebih dari satu = Bertahap.
     */
    protected function dataProyek(array $timpa = []): array
    {
        return array_merge([
            'judul' => 'Redesain Logo Baju',
            'kategori' => self::KAT_DESAIN,
            'keterampilan' => [self::KET_FIGMA, self::KET_KEMASAN],
            'deskripsi' => 'Uniqlo memperbarui logo baju.',
            'hasil' => 'Desain logo baju 3 varian siap cetak.',
            'tenggat_hari' => 7,
            'target_hari' => 30,
            'kuota' => 2,
            'kompensasi' => 2500000,
            'termin' => [[2500000, 'Dibayar penuh setelah file desain final diserahkan dan disetujui']],
        ], $timpa);
    }

    /** UMKM (sudah login): Proyek Saya, Buat Proyek, isi seluruh form. Belum menekan tombol simpan. */
    protected function isiFormProyekBaru(Browser $browser, array $data): Browser
    {
        $this->klikTautan($browser, 'Proyek Saya')->assertPathIs('/umkm/proyek');
        $browser->click('@buat-proyek')
            ->waitForLocation('/umkm/proyek/buat')
            ->waitFor('#title')
            ->type('title', $data['judul']);

        (new WebDriverSelect($browser->driver->findElement(WebDriverBy::id('category_id'))))
            ->selectByVisibleText($data['kategori']);

        foreach ($data['keterampilan'] as $keterampilan) {
            $browser->clickAtXPath("//label[contains(@class,'pick')][span[normalize-space()=".$this->xpathLiteral($keterampilan).']]');
        }

        $browser->type('description', $data['deskripsi'])
            ->type('expected_output', $data['hasil']);

        // Input bertipe date diisi lewat nilai ISO agar tidak bergantung pada format tanggal locale Chrome.
        $browser->driver->executeScript(
            "document.getElementById('application_deadline').value = arguments[0];"
            ."document.getElementById('target_completion_date').value = arguments[1];",
            [
                Carbon::today()->addDays($data['tenggat_hari'])->format('Y-m-d'),
                Carbon::today()->addDays($data['target_hari'])->format('Y-m-d'),
            ]
        );

        $browser->type('quota', (string) $data['kuota'])
            ->type('total_compensation', (string) $data['kompensasi']);

        $bertahap = count($data['termin']) > 1;
        $browser->radio('payment_method', $bertahap ? 'bertahap' : 'sekali');

        if ($bertahap) {
            while (count($browser->driver->findElements(WebDriverBy::cssSelector('#tabel-termin tbody .baris-termin'))) < count($data['termin'])) {
                $browser->click('@tambah-termin');
            }
        }

        $baris = $browser->driver->findElements(WebDriverBy::cssSelector('#tabel-termin tbody .baris-termin'));
        foreach ($data['termin'] as $i => [$nominal, $kondisi]) {
            $kolomKondisi = $baris[$i]->findElement(WebDriverBy::cssSelector('input[name$="[condition]"]'));
            $kolomKondisi->clear();
            $kolomKondisi->sendKeys($kondisi);

            if ($bertahap) {
                $kolomNominal = $baris[$i]->findElement(WebDriverBy::cssSelector('.term-amount'));
                $kolomNominal->clear();
                $kolomNominal->sendKeys((string) $nominal);
            }
        }

        return $browser->waitForTextIn('@status-termin', 'Jumlah termin sesuai total kompensasi');
    }

    /** Klik tombol pada header form setelah tombol digulir ke tengah layar. */
    protected function klikTombolForm(Browser $browser, string $dusk): Browser
    {
        $browser->waitFor("@{$dusk}")
            ->script("document.querySelector('[dusk=\"{$dusk}\"]').scrollIntoView({block: 'center', behavior: 'instant'});");

        return $browser->pause(300)->click("@{$dusk}");
    }

    /** UMKM (sudah login): isi form lalu Simpan sebagai Draf. Mengembalikan id proyek. */
    protected function buatDrafProyek(Browser $browser, array $data): int
    {
        $this->isiFormProyekBaru($browser, $data);
        $this->klikTombolForm($browser, 'simpan-draf')
            ->waitForText(self::MSG_DRAF_DISIMPAN);

        return $this->idDariUrl($browser, '#/umkm/proyek/(\d+)/ubah#');
    }

    /** UMKM (sudah login, di halaman form proyek): Tinjau & Ajukan, centang pernyataan, Ajukan untuk Moderasi. */
    protected function tinjauDanAjukan(Browser $browser): int
    {
        $this->klikTombolForm($browser, 'tinjau-ajukan')
            ->waitForText('Periksa kembali sebelum mengajukan.');
        $id = $this->idDariUrl($browser, '#/umkm/proyek/(\d+)/tinjau#');

        $browser->check('@pernyataan')
            ->press('@ajukan-moderasi')
            ->waitForText(self::MSG_DIAJUKAN);

        return $id;
    }

    /** UMKM (sudah login): isi form lalu langsung diajukan ke moderasi. Mengembalikan id proyek. */
    protected function buatDanAjukanProyek(Browser $browser, array $data): int
    {
        $this->isiFormProyekBaru($browser, $data);

        return $this->tinjauDanAjukan($browser);
    }

    /**
     * Admin (sudah login): menu Moderasi Proyek, pilih proyek pada antrean Menunggu, beri keputusan.
     * $keputusan: approved | revision_requested | rejected.
     */
    protected function moderasiLewatMenu(Browser $browser, int $proyekId, string $judul, string $keputusan, ?string $catatan = null): Browser
    {
        $this->klikTautan($browser, 'Moderasi Proyek')->assertPathIs('/admin/moderasi-proyek');

        $this->tandaiHalamanLama($browser);
        $akhiran = "project={$proyekId}";
        $browser->clickAtXPath("//a[substring(@href, string-length(@href) - string-length('{$akhiran}') + 1) = '{$akhiran}']");
        $this->tungguHalamanBaru($browser)
            ->waitForText('Pemeriksaan & Keputusan')
            ->assertSee($judul)
            ->radio('decision', $keputusan);

        if ($catatan !== null) {
            $browser->type('#note', $catatan);
        }

        return $browser->press('Simpan Keputusan')->waitForText(self::MSG_MODERASI_DISIMPAN);
    }

    /** UMKM membuat dan mengajukan proyek, lalu Admin menyetujuinya. Dimulai dan diakhiri tanpa sesi login. */
    protected function terbitkanProyekUmkm(Browser $browser, array $akun, array $data): int
    {
        $this->masukSebagaiUmkm($browser, $akun);
        $id = $this->buatDanAjukanProyek($browser, $data);
        $this->keluar($browser);

        $this->masukSebagaiAdmin($browser);
        $this->moderasiProyek($browser, $id, 'approved');
        $this->keluar($browser);

        return $id;
    }

    /** Status proyek di database (verifikasi tambahan, read-only). */
    protected function statusProyekDb(int $proyekId): ?string
    {
        return DB::table('projects')->where('id', $proyekId)->value('status');
    }

    protected function jumlahProyekUmkmDb(string $email, ?string $status = null): int
    {
        return DB::table('projects')
            ->where('umkm_profile_id', $this->idProfilUmkm($email))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->count();
    }

    // ------------------------------------------------------------------
    // Proyek Saya (PBI-13): pembacaan tampilan
    // ------------------------------------------------------------------

    /** Nilai keempat kartu ringkasan Proyek Saya. */
    protected function kartuRingkasan(Browser $browser): array
    {
        return [
            'dipublikasikan' => (int) trim($browser->text('@nilai-dipublikasikan')),
            'menunggu' => (int) trim($browser->text('@nilai-menunggu-moderasi')),
            'perbaikan' => (int) trim($browser->text('@nilai-perlu-perbaikan')),
            'pelamar' => (int) trim($browser->text('@nilai-total-pelamar')),
        ];
    }

    /** Tab status Proyek Saya: [label => jumlah]. */
    protected function tabStatusProyek(Browser $browser): array
    {
        $tabs = $browser->driver->executeScript(
            "return [...document.querySelectorAll('[dusk=\"tab-status-proyek\"] a')].map(a => ["
            ."a.childNodes[0].textContent.trim(), a.querySelector('span').textContent.trim()]);"
        );

        $hasil = [];
        foreach ($tabs as [$label, $jumlah]) {
            $hasil[$label] = (int) $jumlah;
        }

        return $hasil;
    }

    /** Id proyek dari semua kartu yang tampil di Proyek Saya, urut dari atas. */
    protected function idKartuProyekSaya(Browser $browser): array
    {
        return array_map('intval', $browser->driver->executeScript(
            "return [...document.querySelectorAll('article[dusk^=\"kartu-proyek-\"]')].map(a => a.getAttribute('dusk').replace('kartu-proyek-', ''));"
        ));
    }

    /** Klik tab status di Proyek Saya dan tunggu halaman baru. */
    protected function klikTabStatus(Browser $browser, string $dusk): Browser
    {
        $this->tandaiHalamanLama($browser);
        $browser->click("@{$dusk}");

        return $this->tungguHalamanBaru($browser);
    }

    // ------------------------------------------------------------------
    // Kandidat
    // ------------------------------------------------------------------

    /** Kandidat baru dengan 6 data diri dan keterampilan Desain Kemasan, dibuat lewat UI. Diakhiri tanpa sesi login. */
    protected function siapkanKandidatExcel(Browser $browser, string $label): array
    {
        $this->pastikanMasterDataExcel($browser);

        $akun = $this->daftarKandidatBaru($browser, $label);
        $this->isiDataDiri($browser, $akun['nama']);
        $this->tambahKeterampilan($browser, self::KET_KEMASAN);
        $this->keluar($browser);

        return $akun;
    }

    /** Kandidat (sudah login): menu Eksplor Proyek. */
    protected function bukaEksplor(Browser $browser): Browser
    {
        return $this->klikTautan($browser, 'Eksplor Proyek')
            ->assertPathIs('/kandidat/proyek')
            ->waitFor('@form-filter');
    }

    /** Kandidat (di Eksplor Proyek): klik Terapkan dan tunggu hasil dimuat ulang. */
    protected function terapkanFilter(Browser $browser): Browser
    {
        $this->tandaiHalamanLama($browser);
        $browser->press('@terapkan-filter');

        return $this->tungguHalamanBaru($browser)->assertPathIs('/kandidat/proyek');
    }

    /** XPath kartu proyek di Eksplor Proyek berdasarkan judul persis. */
    protected function xpathKartuEksplor(string $judul): string
    {
        return "//article[@dusk='kartu-proyek'][.//a[@dusk='judul-proyek' and normalize-space()=".$this->xpathLiteral($judul).']]';
    }

    /** Teks kartu proyek di Eksplor Proyek (innerText), atau null bila kartu tidak tampil. */
    protected function teksKartuEksplor(Browser $browser, string $judul): ?string
    {
        $kartu = $browser->driver->findElements(WebDriverBy::xpath($this->xpathKartuEksplor($judul)));

        return $kartu === [] ? null : $kartu[0]->getText();
    }

    /** Judul kartu di Eksplor Proyek sesuai urutan tampil. */
    protected function judulKartuEksplor(Browser $browser): array
    {
        return $browser->driver->executeScript(
            "return [...document.querySelectorAll('article[dusk=\"kartu-proyek\"] a[dusk=\"judul-proyek\"]')].map(a => a.textContent.trim());"
        );
    }

    /** Parameter query string halaman saat ini. */
    protected function queryHalaman(Browser $browser): array
    {
        parse_str((string) parse_url($browser->driver->getCurrentURL(), PHP_URL_QUERY), $query);

        return $query;
    }

    // ------------------------------------------------------------------
    // Katalog uji PBI-15 (dipakai ulang per hari)
    // ------------------------------------------------------------------

    /**
     * Memastikan katalog uji PBI-15 hari ini sudah Dipublikasikan oleh UMKM "Kopi Nusantara PBI15", lalu
     * mengembalikan kode katalog [K]. Bila katalog hari ini belum lengkap, katalog baru dibuat lewat UI
     * (UMKM membuat 3 proyek, Admin menyetujui). Dimulai dan diakhiri tanpa sesi login.
     *
     * (A) Desain Logo Kedai Kopi [K] - Desain Grafis & Branding, Figma, Rp1.500.000, tenggat +20
     * (B) Poster Promosi Bulanan [K] - Pemasaran Digital, Copywriting, Rp800.000, tenggat +5
     * (C) Katalog Produk Digital [K] - Desain Grafis & Branding, Figma dan Copywriting, Rp3.000.000, tenggat +12
     */
    protected function pastikanKatalogPbi15(Browser $browser): string
    {
        $this->pastikanMasterDataExcel($browser);

        if (($kode = $this->kodeKatalogPbi15Tersedia()) !== null) {
            return $kode;
        }

        $akun = $this->pastikanUmkmPbi15($browser);
        $kode = 'PBI15-'.Carbon::today()->format('Ymd').'-'.Str::lower(Str::random(3));

        $this->masukSebagaiUmkm($browser, $akun);
        $ids = [];
        foreach ($this->rencanaKatalogPbi15($kode) as $data) {
            $ids[] = $this->buatDanAjukanProyek($browser, $data);
        }
        $this->keluar($browser);

        $this->masukSebagaiAdmin($browser);
        foreach ($ids as $id) {
            $this->moderasiProyek($browser, $id, 'approved');
        }
        $this->keluar($browser);

        return $kode;
    }

    /** @return array{a: string, b: string, c: string} */
    protected function judulKatalogPbi15(string $kode): array
    {
        return [
            'a' => "Desain Logo Kedai Kopi {$kode}",
            'b' => "Poster Promosi Bulanan {$kode}",
            'c' => "Katalog Produk Digital {$kode}",
        ];
    }

    private function rencanaKatalogPbi15(string $kode): array
    {
        $judul = $this->judulKatalogPbi15($kode);

        return [
            $this->dataProyek([
                'judul' => $judul['a'], 'kategori' => self::KAT_DESAIN, 'keterampilan' => [self::KET_FIGMA],
                'deskripsi' => 'Membuat logo baru untuk kedai kopi yang akan membuka cabang kedua.',
                'hasil' => 'File logo siap pakai', 'tenggat_hari' => 20, 'target_hari' => 45, 'kuota' => 1,
                'kompensasi' => 1500000, 'termin' => [[1500000, 'Dibayar penuh setelah logo final disetujui']],
            ]),
            $this->dataProyek([
                'judul' => $judul['b'], 'kategori' => self::KAT_PEMASARAN, 'keterampilan' => [self::KET_COPYWRITING],
                'deskripsi' => 'Menulis naskah dan materi promosi media sosial untuk satu bulan.',
                'hasil' => 'Naskah promosi bulanan', 'tenggat_hari' => 5, 'target_hari' => 30, 'kuota' => 1,
                'kompensasi' => 800000, 'termin' => [[800000, 'Dibayar penuh setelah materi promosi disetujui']],
            ]),
            $this->dataProyek([
                'judul' => $judul['c'], 'kategori' => self::KAT_DESAIN, 'keterampilan' => [self::KET_FIGMA, self::KET_COPYWRITING],
                'deskripsi' => 'Menyusun katalog produk kopi dalam format digital beserta teks deskripsinya.',
                'hasil' => 'Katalog produk digital', 'tenggat_hari' => 12, 'target_hari' => 40, 'kuota' => 1,
                'kompensasi' => 3000000, 'termin' => [[3000000, 'Dibayar penuh setelah katalog final disetujui']],
            ]),
        ];
    }

    /** Kode katalog hari ini yang ketiga proyeknya sudah Dipublikasikan dengan data sesuai Excel (read-only). */
    private function kodeKatalogPbi15Tersedia(): ?string
    {
        $prefix = 'Desain Logo Kedai Kopi PBI15-'.Carbon::today()->format('Ymd').'-';

        $kandidat = DB::table('projects')
            ->join('umkm_profiles', 'umkm_profiles.id', '=', 'projects.umkm_profile_id')
            ->where('umkm_profiles.business_name', self::UMKM_PBI15)
            ->where('projects.title', 'like', $prefix.'%')
            ->orderByDesc('projects.id')
            ->pluck('projects.title');

        foreach ($kandidat as $judulA) {
            $kode = Str::after($judulA, 'Desain Logo Kedai Kopi ');
            $harapan = [
                ['a', 1500000, 20],
                ['b', 800000, 5],
                ['c', 3000000, 12],
            ];
            $judul = $this->judulKatalogPbi15($kode);

            $lengkap = collect($harapan)->every(fn ($h) => DB::table('projects')
                ->where('title', $judul[$h[0]])
                ->where('status', 'published')
                ->where('total_compensation', $h[1])
                ->whereDate('application_deadline', Carbon::today()->addDays($h[2])->toDateString())
                ->count() === 1);

            if ($lengkap) {
                return $kode;
            }
        }

        return null;
    }

    /** Akun UMKM bersama "Kopi Nusantara PBI15": login bila sudah ada, registrasi bila belum, lalu diverifikasi. */
    private function pastikanUmkmPbi15(Browser $browser): array
    {
        $akun = [
            'email' => (string) env('DUSK_UMKM_PBI15_EMAIL', 'dusk.umkm.pbi15@proyekkita.test'),
            'sandi' => (string) env('DUSK_UMKM_PBI15_PASSWORD', self::SANDI_UJI),
        ];

        if (! $this->cobaMasuk($browser, $akun['email'], $akun['sandi'])) {
            $this->daftar($browser, 'umkm', 'Penanggung Jawab Kopi Nusantara', $akun['email'], $akun['sandi']);
        }

        $status = trim($browser->visit('/umkm/profil')->waitFor('@status-verifikasi')->text('@status-verifikasi'));
        if ($status === 'Belum diisi') {
            $this->isiProfilUsaha($browser, [
                'nama' => self::UMKM_PBI15, 'bidang' => 'Kuliner & Minuman',
                'deskripsi' => 'Kedai kopi lokal yang menjual biji kopi dan minuman kopi.',
                'kontak' => '0812-3456-7815', 'kota' => 'Bandung',
                'alamat' => 'Jl. Braga No. 15 Bandung', 'nib' => '1234567890115',
            ]);
            $status = trim($browser->visit('/umkm/profil')->waitFor('@status-verifikasi')->text('@status-verifikasi'));
        }
        $this->keluar($browser);

        if ($status === 'Ditolak') {
            throw new RuntimeException("Profil usaha {$akun['email']} berstatus Ditolak. Isi DUSK_UMKM_PBI15_EMAIL dengan akun baru.");
        }

        if ($status !== 'Terverifikasi') {
            $this->verifikasiUmkm($browser, $akun['email']);
        }

        return $akun;
    }
}
