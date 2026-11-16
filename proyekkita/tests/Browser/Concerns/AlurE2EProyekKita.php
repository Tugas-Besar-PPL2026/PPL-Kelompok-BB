<?php

namespace Tests\Browser\Concerns;

use Closure;
use Facebook\WebDriver\Exception\NoSuchAlertException;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverSelect;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use ReflectionFunction;
use RuntimeException;

/**
 * Helper End-to-End (E2E) untuk PBI-16, PBI-17, dan PBI-18.
 *
 * Prinsip:
 *  1. SEMUA data skenario dibuat lewat UI aplikasi di Chrome: registrasi, profil, master data, proyek,
 *     verifikasi UMKM, moderasi proyek, lamaran, keputusan seleksi, dan pembatalan.
 *     Tidak ada factory, seeder, SQL INSERT, atau loginAs().
 *  2. Database hanya DIBACA (DB::table(...)) sebagai verifikasi tambahan setelah aksi di browser.
 *     Satu-satunya pengecualian ada di simulasikanKondisiProyek(), yang hanya aktif bila
 *     DUSK_IZINKAN_SIMULASI_DB=true (lihat penjelasan di method tersebut).
 *  3. Database persistent: setiap test membuat data baru dengan kode unik (judul proyek, email kandidat),
 *     sehingga test dapat dijalankan berulang kali dan tidak bergantung pada urutan test.
 *     Data bersama yang dipakai ulang bila sudah valid: master data (kategori & keterampilan) dan
 *     satu akun UMKM terverifikasi. Keduanya dibuat lewat UI bila belum ada.
 *  4. Setiap helper persiapan dimulai TANPA sesi login dan diakhiri dengan Keluar,
 *     sehingga langkah test sendiri selalu dimulai dari halaman login seperti pada Excel.
 */
trait AlurE2EProyekKita
{
    // ------------------------------------------------------------------
    // Data bersama (nama dibuat khusus agar tidak bentrok dengan data development)
    // ------------------------------------------------------------------

    protected const KATEGORI_E2E = 'Dusk Desain Kreatif';

    protected const KETERAMPILAN_E2E = 'Dusk Desain Grafis';

    protected const SANDI_E2E = 'Password123!';

    protected const NAMA_USAHA_E2E = 'Dusk UMKM Lamaran';

    /** Pesan valid sesuai kolom Input Excel (TC-PBI16-001 dan lainnya). */
    protected const PESAN_VALID = 'Halo, saya berpengalaman mendesain label kemasan UMKM dan tertarik membantu proyek ini.';

    // Teks yang benar-benar dipakai aplikasi (ApplicationService, ApplicationRequest, Blade).
    protected const MSG_LAMARAN_TERKIRIM = 'Lamaran berhasil dikirim dan berstatus Menunggu Keputusan.';

    protected const MSG_LAMARAN_GANDA = 'Anda sudah pernah melamar proyek ini. Satu kandidat hanya dapat melamar sekali untuk setiap proyek.';

    protected const MSG_LAMAR_ULANG_BATAL = 'Anda sudah pernah mengajukan lamaran pada proyek ini. Lamaran yang sudah dibatalkan tidak dapat diajukan kembali.';

    protected const MSG_PROYEK_DITUTUP = 'Proyek ini sudah ditutup dan tidak lagi menerima lamaran.';

    protected const MSG_TENGGAT_LEWAT = 'Tenggat lamaran proyek ini sudah berakhir.';

    protected const MSG_BATAL_SUKSES = 'Lamaran berhasil dibatalkan dan berstatus Dibatalkan.';

    protected const MSG_TANPA_AKSES = 'Anda tidak memiliki akses ke halaman ini.';

    private static bool $masterDataSiap = false;

    private static bool $umkmSiap = false;

    private static bool $serverDiperiksa = false;

    // ------------------------------------------------------------------
    // Browser bersih di awal setiap test
    // ------------------------------------------------------------------

    /**
     * Dusk memakai ulang browser utama antar-test dalam satu kelas, sehingga cookie login dan ukuran jendela
     * dari test sebelumnya bisa terbawa. Sebelum callback test dijalankan, setiap browser dibuka di halaman
     * awal, cookie dihapus (tanpa sesi login), dan ukuran jendela dikembalikan ke ukuran desktop.
     * Jumlah parameter callback dipertahankan agar Dusk tetap membuka jumlah browser yang sama.
     */
    public function browse(Closure $callback)
    {
        $jalankan = function (array $browsers) use ($callback) {
            foreach ($browsers as $browser) {
                $browser->visit('/');
                $browser->driver->manage()->deleteAllCookies();
                $browser->resize(1440, 1000);
            }

            return $callback(...$browsers);
        };

        return parent::browse(match ((new ReflectionFunction($callback))->getNumberOfParameters()) {
            1 => fn (Browser $a) => $jalankan([$a]),
            2 => fn (Browser $a, Browser $b) => $jalankan([$a, $b]),
            default => fn (Browser $a, Browser $b, Browser $c) => $jalankan([$a, $b, $c]),
        });
    }

    /** Klik tautan (teks) lalu tunggu sampai halaman tujuan benar-benar dimuat ulang. */
    protected function klikTautan(Browser $browser, string $teks): Browser
    {
        $this->tandaiHalamanLama($browser);
        $browser->clickLink($teks);

        return $this->tungguHalamanBaru($browser);
    }

    // ------------------------------------------------------------------
    // Konfigurasi akun bersama (dapat diubah lewat .env.dusk.local)
    // ------------------------------------------------------------------

    protected function emailAdmin(): string
    {
        return (string) env('DUSK_ADMIN_EMAIL', 'admin@proyekkita.test');
    }

    protected function sandiAdmin(): string
    {
        $sandi = env('DUSK_ADMIN_PASSWORD');

        if (! is_string($sandi) || $sandi === '') {
            throw new RuntimeException('Isi DUSK_ADMIN_PASSWORD di .env.dusk.local dengan password akun Admin yang dibuat manual di database test.');
        }

        return $sandi;
    }

    protected function emailUmkm(): string
    {
        return (string) env('DUSK_UMKM_EMAIL', 'dusk.umkm.lamaran@proyekkita.test');
    }

    protected function sandiUmkm(): string
    {
        return (string) env('DUSK_UMKM_PASSWORD', self::SANDI_E2E);
    }

    protected function kodeUnik(): string
    {
        return now()->format('mdHis').Str::lower(Str::random(4));
    }

    // ------------------------------------------------------------------
    // Akun: login, keluar, registrasi
    // ------------------------------------------------------------------

    /** Login lewat halaman /login (bukan loginAs). */
    protected function masuk(Browser $browser, string $email, string $sandi, string $halamanAwal): Browser
    {
        return $browser->visit('/login')
            ->waitFor('@masuk')
            ->type('email', $email)
            ->type('password', $sandi)
            ->press('@masuk')
            ->waitForLocation($halamanAwal)
            ->assertPathIs($halamanAwal);
    }

    protected function masukSebagaiKandidat(Browser $browser, array $akun): Browser
    {
        return $this->masuk($browser, $akun['email'], $akun['sandi'], '/kandidat/profil')
            ->assertSee('Profil & Portofolio');
    }

    protected function masukSebagaiUmkm(Browser $browser, ?array $akun = null): Browser
    {
        return $this->masuk($browser, $akun['email'] ?? $this->emailUmkm(), $akun['sandi'] ?? $this->sandiUmkm(), '/umkm/profil');
    }

    protected function masukSebagaiAdmin(Browser $browser): Browser
    {
        return $this->masuk($browser, $this->emailAdmin(), $this->sandiAdmin(), '/admin/data-master');
    }

    /** Mencoba login. Mengembalikan false bila aplikasi menolak (akun belum ada / sandi salah). */
    protected function cobaMasuk(Browser $browser, string $email, string $sandi): bool
    {
        $browser->visit('/login')
            ->waitFor('@masuk')
            ->type('email', $email)
            ->type('password', $sandi)
            ->press('@masuk')
            ->waitUntil("window.location.pathname !== '/login' || document.querySelector('[dusk=\"galat-login\"]') !== null");

        return parse_url($browser->driver->getCurrentURL(), PHP_URL_PATH) !== '/login';
    }

    /** Klik tombol Keluar di menu samping. */
    protected function keluar(Browser $browser): Browser
    {
        return $browser->press('Keluar')->waitForLocation('/');
    }

    /** Registrasi lewat /daftar/{peran}. Pengguna langsung masuk setelah registrasi berhasil. */
    protected function daftar(Browser $browser, string $peran, string $nama, string $email, string $sandi): Browser
    {
        $halamanAwal = $peran === 'umkm' ? '/umkm/profil' : '/kandidat/profil';

        $browser->visit("/daftar/{$peran}")
            ->waitFor('@daftar')
            ->type('name', $nama)
            ->type('email', $email)
            ->type('password', $sandi)
            ->type('password_confirmation', $sandi)
            ->check('terms')
            ->press('@daftar')
            ->waitForLocation($halamanAwal)
            ->assertSee('Akun berhasil dibuat. Selamat datang di ProyekKita.');

        $this->pastikanServerMemakaiDatabaseTest($email);

        return $browser;
    }

    /**
     * Server yang dibuka Chrome harus memakai database yang sama dengan proses test.
     * Dibuktikan dengan membaca akun yang baru saja didaftarkan lewat UI.
     */
    private function pastikanServerMemakaiDatabaseTest(string $email): void
    {
        if (self::$serverDiperiksa) {
            return;
        }

        if (! DB::table('users')->where('email', mb_strtolower($email))->exists()) {
            throw new RuntimeException(
                'Akun yang baru didaftarkan lewat browser tidak ditemukan di database test. '
                .'Server aplikasi (APP_URL) kemungkinan memakai database lain. Hentikan test dan periksa .env.dusk.local.'
            );
        }

        self::$serverDiperiksa = true;
    }

    // ------------------------------------------------------------------
    // Master data (Admin) - dipakai ulang bila sudah ada
    // ------------------------------------------------------------------

    /** Memastikan kategori dan keterampilan khusus E2E tersedia. Dibuat Admin lewat halaman Data Master bila belum ada. */
    protected function pastikanMasterData(Browser $browser): void
    {
        if (self::$masterDataSiap) {
            return;
        }

        $this->masukSebagaiAdmin($browser);
        $browser->visit('/admin/data-master')->waitFor('@kartu-keterampilan');

        if (! $this->halamanMemuat($browser, self::KATEGORI_E2E)) {
            $browser->type('form[action$="/admin/data-master/kategori"] input[name="name"]', self::KATEGORI_E2E)
                ->click('form[action$="/admin/data-master/kategori"] button[type="submit"]')
                ->waitForText(self::KATEGORI_E2E);
        }

        if (! $this->halamanMemuat($browser, self::KETERAMPILAN_E2E)) {
            $browser->type('@input-keterampilan-baru', self::KETERAMPILAN_E2E)
                ->press('@tambah-keterampilan')
                ->waitForText('Keterampilan baru berhasil ditambahkan.');
        }

        $this->keluar($browser);
        self::$masterDataSiap = true;
    }

    // ------------------------------------------------------------------
    // UMKM bersama yang sudah terverifikasi - dipakai ulang bila sudah ada
    // ------------------------------------------------------------------

    protected function pastikanUmkmTerverifikasi(Browser $browser): void
    {
        if (self::$umkmSiap) {
            return;
        }

        $this->pastikanMasterData($browser);

        $akun = ['email' => $this->emailUmkm(), 'sandi' => $this->sandiUmkm()];

        if (! $this->cobaMasuk($browser, $akun['email'], $akun['sandi'])) {
            $this->daftar($browser, 'umkm', 'Penanggung Jawab Dusk', $akun['email'], $akun['sandi']);
        }

        $this->siapkanUmkmTerverifikasi($browser, $akun, self::NAMA_USAHA_E2E);
        self::$umkmSiap = true;
    }

    /**
     * UMKM (sudah login) mengisi profil usaha bila belum ada, lalu Admin memverifikasinya bila belum terverifikasi.
     * Diakhiri tanpa sesi login.
     */
    protected function siapkanUmkmTerverifikasi(Browser $browser, array $akun, string $namaUsaha): void
    {
        $browser->visit('/umkm/profil')->waitFor('@status-verifikasi');

        if (trim($browser->text('@status-verifikasi')) === 'Belum diisi') {
            $browser->type('@nama-usaha', $namaUsaha)
                ->type('@bidang-usaha', 'Jasa')
                ->type('@deskripsi-usaha', 'Usaha percetakan dan kemasan untuk pengujian otomatis.')
                ->type('@kontak-usaha', '081234567890')
                ->type('@kota-usaha', 'Bandung')
                ->type('@alamat-usaha', 'Jl. Pengujian No. 1, Bandung')
                ->type('@nib', '1234567890123')
                ->press('@simpan-profil')
                ->waitForText('Profil usaha berhasil disimpan.');
        }

        $status = trim($browser->visit('/umkm/profil')->waitFor('@status-verifikasi')->text('@status-verifikasi'));
        $this->keluar($browser);

        if ($status === 'Terverifikasi') {
            return;
        }

        if ($status === 'Ditolak') {
            throw new RuntimeException("Profil usaha {$akun['email']} berstatus Ditolak. Ganti DUSK_UMKM_EMAIL dengan akun baru.");
        }

        // Admin memverifikasi UMKM lewat halaman Verifikasi UMKM.
        $this->masukSebagaiAdmin($browser);
        $browser->visit('/admin/verifikasi?status=pending')
            ->waitForText($namaUsaha)
            ->clickAtXPath("//a[starts-with(@dusk,'item-usaha-')][b[normalize-space()=".$this->xpathLiteral($namaUsaha).']]')
            ->waitForTextIn('@detail-usaha', $namaUsaha)
            ->press('@btn-setujui')
            ->waitForText('Akun UMKM berhasil diverifikasi.');
        $this->keluar($browser);
    }

    // ------------------------------------------------------------------
    // Proyek (UMKM membuat, Admin memoderasi)
    // ------------------------------------------------------------------

    /**
     * UMKM bersama membuat proyek lewat form, mengajukannya, lalu Admin menyetujuinya sehingga berstatus Dipublikasikan.
     *
     * @param  list<string>  $judul
     * @return array<string, int> judul => id proyek (id dibaca dari URL halaman Tinjau)
     */
    protected function terbitkanProyek(Browser $browser, array $judul, array $opsi = []): array
    {
        $this->pastikanUmkmTerverifikasi($browser);

        $this->masukSebagaiUmkm($browser);
        $ids = [];
        foreach ($judul as $item) {
            $ids[$item] = $this->buatProyek($browser, $item, 'ajukan', $opsi);
        }
        $this->keluar($browser);

        $this->masukSebagaiAdmin($browser);
        foreach ($ids as $id) {
            $this->moderasiProyek($browser, $id, 'approved');
        }
        $this->keluar($browser);

        return $ids;
    }

    /** Satu proyek Dipublikasikan dengan judul unik. */
    protected function terbitkanSatuProyek(Browser $browser, string $label): array
    {
        $judul = "Dusk {$label} {$this->kodeUnik()}";
        $id = $this->terbitkanProyek($browser, [$judul])[$judul];

        return ['id' => $id, 'judul' => $judul];
    }

    /**
     * UMKM (sudah login) mengisi form Buat Proyek.
     * $mode: 'draf' (Simpan Draf) atau 'ajukan' (Tinjau & Ajukan lalu ajukan ke moderasi).
     */
    protected function buatProyek(Browser $browser, string $judul, string $mode = 'ajukan', array $opsi = []): int
    {
        $tenggat = Carbon::today()->addDays($opsi['tenggat_hari'] ?? 14)->format('Y-m-d');
        $target = Carbon::today()->addDays(($opsi['tenggat_hari'] ?? 14) + 30)->format('Y-m-d');

        $browser->visit('/umkm/proyek')
            ->waitFor('@buat-proyek')
            ->click('@buat-proyek')
            ->waitForLocation('/umkm/proyek/buat')
            ->type('title', $judul);

        (new WebDriverSelect($browser->driver->findElement(WebDriverBy::id('category_id'))))
            ->selectByVisibleText(self::KATEGORI_E2E);

        $browser->clickAtXPath("//label[contains(@class,'pick')][span[normalize-space()=".$this->xpathLiteral(self::KETERAMPILAN_E2E).']]')
            ->type('description', 'Membuat desain label kemasan produk untuk pengujian otomatis.')
            ->type('expected_output', 'Desain label kemasan siap cetak');

        // Input bertipe date diisi lewat nilai ISO agar tidak bergantung pada format tanggal locale Chrome.
        $browser->driver->executeScript(
            "document.getElementById('application_deadline').value = arguments[0];"
            ."document.getElementById('target_completion_date').value = arguments[1];",
            [$tenggat, $target]
        );

        $browser->type('quota', (string) ($opsi['kuota'] ?? 1))
            ->type('total_compensation', '1500000')
            ->radio('payment_method', 'sekali')
            ->type('terms[0][condition]', 'Dibayar setelah hasil pekerjaan diterima');

        if ($mode === 'draf') {
            $browser->waitFor('@simpan-draf')
            ->script("
                const tombol = document.querySelector('[dusk=\"simpan-draf\"]');
                tombol.scrollIntoView({
                    behavior: 'instant',
                    block: 'center',
                    inline: 'center'
                });
            ");

        $browser->pause(500)
            ->click('@simpan-draf');

            return $this->idDariUrl($browser, '#/umkm/proyek/(\d+)/ubah#');
        }

        $browser->waitFor('@tinjau-ajukan')
            ->script("
                const tombol = document.querySelector('[dusk=\"tinjau-ajukan\"]');
                tombol.scrollIntoView({
                    behavior: 'instant',
                    block: 'center',
                    inline: 'center'
                });
            ");

        $browser->pause(500)
            ->click('@tinjau-ajukan')
            ->waitForText('Periksa kembali sebelum mengajukan.');
        $id = $this->idDariUrl($browser, '#/umkm/proyek/(\d+)/tinjau#');

        $browser->check('@pernyataan')
            ->press('@ajukan-moderasi')
            ->waitForText('Proyek diajukan dan menunggu moderasi admin.');

        return $id;
    }

    /** Admin (sudah login) memberi keputusan moderasi: approved | revision_requested | rejected. */
    protected function moderasiProyek(Browser $browser, int $proyekId, string $keputusan, ?string $catatan = null): void
    {
        $browser->visit("/admin/moderasi-proyek?status=pending_review&project={$proyekId}")
            ->waitForText('Pemeriksaan & Keputusan')
            ->radio('decision', $keputusan);

        if ($catatan !== null) {
            $browser->type('#note', $catatan);
        }

        $browser->press('Simpan Keputusan')
            ->waitForText('Keputusan moderasi proyek berhasil disimpan.');
    }

    /**
     * Admin (sudah login) memastikan proyek sudah berstatus Dipublikasikan: proyek tampil pada tab "Disetujui"
     * di halaman Moderasi Proyek dengan badge "Disetujui" (tab ini hanya memuat proyek Dipublikasikan/Ditutup).
     */
    protected function adminMemastikanProyekDipublikasikan(Browser $browser, int $proyekId, string $judul): void
    {
        $browser->visit("/admin/moderasi-proyek?status=published&project={$proyekId}")
            ->waitForText($judul)
            ->assertSee('Disetujui');
    }

    // ------------------------------------------------------------------
    // Kandidat
    // ------------------------------------------------------------------

    /** Registrasi kandidat baru lewat UI. Setelah registrasi pengguna sudah login. */
    protected function daftarKandidatBaru(Browser $browser, string $label = 'Kandidat'): array
    {
        $kode = $this->kodeUnik();
        $akun = [
            'nama' => "{$label} Dusk {$kode}",
            'email' => 'dusk.kandidat.'.$kode.'@proyekkita.test',
            'sandi' => self::SANDI_E2E,
        ];

        $this->daftar($browser, 'kandidat', $akun['nama'], $akun['email'], $akun['sandi']);

        return $akun;
    }

    /** Kandidat (sudah login) mengisi 6 data diri lewat tab Data Diri. */
    protected function isiDataDiri(Browser $browser, string $namaLengkap): void
    {
        $browser->visit('/kandidat/profil')->waitFor('#cari-keterampilan');
        $this->klikTautan($browser, 'Data Diri')
            ->assertPathIs('/kandidat/profil/data-diri')
            ->type('@nama-lengkap', $namaLengkap)
            ->type('@domisili', 'Bandung')
            ->type('@headline', 'Mahasiswa Desain Komunikasi Visual')
            ->type('@universitas', 'Universitas Pengujian')
            ->type('@program-studi', 'Desain Komunikasi Visual')
            ->type('@ringkasan', 'Berpengalaman membuat desain kemasan dan media promosi untuk UMKM.')
            ->press('@simpan-profil')
            ->waitForText('Data diri berhasil disimpan.');
    }

    /** Kandidat (sudah login) menambah keterampilan dari data master di halaman Profil & Portofolio. */
    protected function tambahKeterampilan(Browser $browser, string $keterampilan = self::KETERAMPILAN_E2E): void
    {
        $browser->visit('/kandidat/profil')
            ->waitFor('#cari-keterampilan')
            ->type('#cari-keterampilan', $keterampilan)
            ->click('[data-skill-name="'.mb_strtolower($keterampilan).'"] .skill-add')
            ->waitForText('Keterampilan berhasil ditambahkan.')
            ->assertSeeIn('#selected-skills', $keterampilan);
    }

    /**
     * Kandidat baru yang memenuhi syarat melamar (6 data diri + 1 keterampilan), dibuat lewat UI.
     * $tingkat: 'lengkap' | 'tanpa-keterampilan' | 'tanpa-profil'. Diakhiri tanpa sesi login.
     */
    protected function siapkanKandidat(Browser $browser, string $label = 'Kandidat', string $tingkat = 'lengkap'): array
    {
        $this->pastikanMasterData($browser);

        $akun = $this->daftarKandidatBaru($browser, $label);

        if ($tingkat !== 'tanpa-profil') {
            $this->isiDataDiri($browser, $akun['nama']);
        }

        if ($tingkat === 'lengkap') {
            $this->tambahKeterampilan($browser);
        }

        $this->keluar($browser);

        return $akun;
    }

    // ------------------------------------------------------------------
    // Lamaran (PBI-16)
    // ------------------------------------------------------------------

    /** Kandidat (sudah login): menu Eksplor Proyek -> cari judul -> Lihat Detail & Lamar. */
    protected function bukaDetailDariEksplor(Browser $browser, string $judul): Browser
    {
        $this->klikTautan($browser, 'Eksplor Proyek')
            ->assertPathIs('/kandidat/proyek')
            ->type('@cari-proyek', $judul)
            ->press('@terapkan-filter')
            ->waitForText($judul)
            ->clickAtXPath(
                "//article[@dusk='kartu-proyek'][.//a[@dusk='judul-proyek' and normalize-space()=".$this->xpathLiteral($judul).']]'
                ."//a[@dusk='lihat-detail']"
            )
            ->waitFor('#form-lamaran')
            ->assertSee($judul);

        return $browser;
    }

    /** Kandidat (sudah login) melamar lewat UI dan menunggu pesan sukses. */
    protected function ajukanLamaran(Browser $browser, string $judul, string $pesan = self::PESAN_VALID): Browser
    {
        return $this->bukaDetailDariEksplor($browser, $judul)
            ->type('@pesan-lamaran', $pesan)
            ->press('@kirim-lamaran')
            ->waitForText(self::MSG_LAMARAN_TERKIRIM);
    }

    /**
     * Mengaktifkan kembali kolom pesan dan tombol yang dinonaktifkan Blade, seperti pengguna yang mengubah
     * halaman lewat DevTools. Dipakai untuk langkah "(Uji HTTP) Kirim POST" pada Excel agar request tetap
     * dikirim dari form asli di browser (bukan request API langsung).
     */
    protected function aktifkanPaksaFormLamaran(Browser $browser): Browser
    {
        $browser->script("document.querySelectorAll('#form-lamaran [disabled]').forEach(el => el.removeAttribute('disabled'));");

        return $browser;
    }

    // ------------------------------------------------------------------
    // Lamaran Saya (PBI-17) & Pembatalan (PBI-18)
    // ------------------------------------------------------------------

    protected function bukaLamaranSaya(Browser $browser): Browser
    {
        return $this->klikTautan($browser, 'Lamaran Saya')
            ->assertPathIs('/kandidat/lamaran')
            ->assertSee('Pantau status setiap lamaran');
    }

    /** ID lamaran dibaca dari atribut id kartu (#lamaran-{id}) yang memuat judul proyek. */
    protected function idLamaranDariKartu(Browser $browser, string $judul): int
    {
        $id = $browser->driver->executeScript(
            "const card = [...document.querySelectorAll('article.la-card')].find(a => a.querySelector('h3')?.textContent.trim() === arguments[0]);"
            .'return card ? card.id : null;',
            [$judul]
        );

        if (! is_string($id) || ! preg_match('/^lamaran-(\d+)$/', $id, $m)) {
            $this->fail("Kartu lamaran untuk proyek \"{$judul}\" tidak ditemukan di halaman Lamaran Saya.");
        }

        return (int) $m[1];
    }

    /** Judul proyek pada kartu lamaran, urut dari atas ke bawah. */
    protected function urutanJudulKartu(Browser $browser): array
    {
        return $browser->driver->executeScript(
            "return [...document.querySelectorAll('article.la-card h3')].map(h => h.textContent.trim());"
        );
    }

    /** Isi tab Lamaran Saya: [label => ['jumlah' => int, 'aktif' => bool]]. */
    protected function isiTabLamaran(Browser $browser): array
    {
        $tabs = $browser->driver->executeScript(
            "return [...document.querySelectorAll('nav.la-tabs a')].map(a => ["
            ."a.childNodes[0].textContent.trim(), a.querySelector('span').textContent.trim(), a.classList.contains('on')]);"
        );

        $hasil = [];
        foreach ($tabs as [$label, $jumlah, $aktif]) {
            $hasil[$label] = ['jumlah' => (int) $jumlah, 'aktif' => (bool) $aktif];
        }

        return $hasil;
    }

    /** Entri riwayat pada kartu: list of [label, tanggal, teks tambahan...]. Dibaca dari DOM (textContent). */
    protected function entriRiwayat(Browser $browser, int $lamaranId): array
    {
        return $browser->driver->executeScript(
            "const card = document.getElementById('lamaran-' + arguments[0]);"
            ."return [...card.querySelectorAll('details.la-hist .tl .it')].map(it => ({"
            ."label: it.querySelector('.t1')?.textContent.trim() ?? null,"
            ."tanggal: it.querySelector('.t2')?.textContent.trim() ?? null,"
            ."teks: [...it.querySelectorAll('.t3')].map(t => t.textContent.trim())}));",
            [$lamaranId]
        );
    }

    protected function riwayatTerbuka(Browser $browser, int $lamaranId): bool
    {
        return (bool) $browser->driver->executeScript(
            "return document.querySelector('#lamaran-' + arguments[0] + ' details.la-hist')?.open === true;",
            [$lamaranId]
        );
    }

    /** Kandidat (sudah login, di Lamaran Saya) membatalkan lamaran lewat dialog konfirmasi. */
    protected function batalkanLewatDialog(Browser $browser, int $lamaranId): Browser
    {
        return $browser->click("@batalkan-lamaran-{$lamaranId}")
            ->waitFor("@dialog-batal-{$lamaranId}")
            ->press("@konfirmasi-batal-{$lamaranId}")
            ->waitForText(self::MSG_BATAL_SUKSES);
    }

    // ------------------------------------------------------------------
    // Seleksi UMKM (PBI-19/20/21) - dipakai untuk menyiapkan status lamaran lewat UI
    // ------------------------------------------------------------------

    /**
     * UMKM (sudah login) memberi keputusan pada pelamar lewat halaman Seleksi Kandidat.
     * $keputusan: shortlist | accept | reject.
     */
    protected function putuskanPelamar(Browser $browser, int $proyekId, string $namaKandidat, string $keputusan, ?string $catatan = null): void
    {
        $this->bukaSeleksiProyek($browser, $proyekId);

        $browser->click('a[aria-label="Lihat detail pelamar '.$namaKandidat.'"]')
            ->waitFor('@panel-keputusan')
            ->click("@opsi-{$keputusan}");

        if ($catatan !== null) {
            $browser->type('@catatan-seleksi', $catatan);
        }

        $pesan = [
            'shortlist' => 'Pelamar dimasukkan ke daftar terpilih sementara.',
            'reject' => 'Pelamar ditolak dan catatan penolakan tersimpan.',
            'accept' => 'Pelamar berhasil diterima.',
        ][$keputusan];

        $browser->press('@simpan-keputusan')->waitForText($pesan);
    }

    /** UMKM (sudah login): menu Seleksi Kandidat lalu pilih proyek pada dropdown "Proyek:". */
    protected function bukaSeleksiProyek(Browser $browser, int $proyekId): Browser
    {
        $this->klikTautan($browser, 'Seleksi Kandidat')->assertPathIs('/umkm/seleksi')->waitFor('#pilih-proyek');

        $select = $browser->driver->findElement(WebDriverBy::id('pilih-proyek'));
        if ($select->getAttribute('value') !== (string) $proyekId) {
            (new WebDriverSelect($select))->selectByValue((string) $proyekId);
            $browser->waitUntil("new URLSearchParams(window.location.search).get('proyek') === '{$proyekId}'");
        }

        return $browser->waitFor('@kuota-daftar');
    }

    /**
     * Menyiapkan beberapa lamaran milik SATU kandidat dengan status berbeda, seluruhnya lewat UI:
     * UMKM menerbitkan proyek -> kandidat melamar (sesuai urutan $rencana) -> UMKM memutuskan -> kandidat membatalkan.
     *
     * Format $rencana: list of ['judul' => ..., 'keputusan' => [['shortlist', catatan|null], ['accept', null]], 'batal' => bool]
     * Diakhiri tanpa sesi login. Mengembalikan judul => id proyek.
     */
    protected function siapkanLamaranBeragam(Browser $browser, array $kandidat, array $rencana): array
    {
        $proyek = $this->terbitkanProyek($browser, array_column($rencana, 'judul'));

        $this->masukSebagaiKandidat($browser, $kandidat);
        foreach ($rencana as $item) {
            $this->ajukanLamaran($browser, $item['judul']);
        }
        $this->keluar($browser);

        if (collect($rencana)->contains(fn ($item) => ! empty($item['keputusan']))) {
            $this->masukSebagaiUmkm($browser);
            foreach ($rencana as $item) {
                foreach ($item['keputusan'] ?? [] as [$keputusan, $catatan]) {
                    $this->putuskanPelamar($browser, $proyek[$item['judul']], $kandidat['nama'], $keputusan, $catatan);
                }
            }
            $this->keluar($browser);
        }

        if (collect($rencana)->contains(fn ($item) => ! empty($item['batal']))) {
            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaLamaranSaya($browser);
            foreach ($rencana as $item) {
                if (! empty($item['batal'])) {
                    $this->batalkanLewatDialog($browser, $this->idLamaranDariKartu($browser, $item['judul']));
                }
            }
            $this->keluar($browser);
        }

        return $proyek;
    }

    // ------------------------------------------------------------------
    // Manipulasi request dari browser (langkah "(Uji HTTP)" pada Excel)
    // ------------------------------------------------------------------

    /**
     * Membuat dan mengirim form dari halaman yang sedang terbuka di Chrome, memakai token CSRF sesi browser itu.
     * Mewakili pengguna yang memanipulasi request lewat DevTools. Request tetap berasal dari browser,
     * memakai cookie sesi login yang sebenarnya, dan hasilnya diperiksa dari halaman yang tampil.
     *
     * @param  list<array{0: string, 1: string}>  $isian  pasangan [nama, nilai] (boleh nama ganda, mis. message[])
     */
    protected function kirimFormManipulasi(Browser $browser, string $metode, string $aksi, array $isian = []): Browser
    {
        $this->tandaiHalamanLama($browser);

        $browser->driver->executeScript(<<<'JS'
            const [metode, aksi, isian] = arguments;
            const token = document.querySelector('meta[name="csrf-token"]')?.content
                || document.querySelector('input[name="_token"]')?.value || '';
            const form = document.createElement('form');
            form.method = metode === 'GET' ? 'GET' : 'POST';
            form.action = aksi;
            const tambah = (nama, nilai) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = nama;
                input.value = nilai;
                form.appendChild(input);
            };
            if (metode !== 'GET') { tambah('_token', token); }
            if (!['GET', 'POST'].includes(metode)) { tambah('_method', metode); }
            for (const [nama, nilai] of isian) { tambah(nama, nilai); }
            document.body.appendChild(form);
            form.submit();
        JS, [$metode, $aksi, $isian]);

        return $this->tungguHalamanBaru($browser);
    }

    /** Memberi penanda pada halaman saat ini agar bisa menunggu halaman berikutnya selesai dimuat. */
    protected function tandaiHalamanLama(Browser $browser): void
    {
        $browser->script('window.__duskHalamanLama = true;');
    }

    protected function tungguHalamanBaru(Browser $browser): Browser
    {
        return $browser->waitUntil("window.__duskHalamanLama !== true && document.readyState === 'complete'");
    }

    /** Halaman galat bawaan Laravel (resources/views/errors belum dibuat di repository). */
    protected function assertHalamanGalat(Browser $browser, int $kode, string $pesan): Browser
    {
        $teksHalaman = $browser->driver
            ->findElement(\Facebook\WebDriver\WebDriverBy::tagName('body'))
            ->getText();

        $this->assertStringContainsString(
            (string) $kode,
            $teksHalaman,
            "Halaman tidak menampilkan kode HTTP {$kode}."
        );

        $this->assertStringContainsStringIgnoringCase(
            $pesan,
            $teksHalaman,
            "Halaman tidak menampilkan pesan: {$pesan}."
        );

        return $browser;
    }

    protected function assertTidakAdaDialogAlert(Browser $browser): void
    {
        try {
            $teks = $browser->driver->switchTo()->alert()->getText();
            $browser->driver->switchTo()->alert()->dismiss();
            $this->fail("Dialog alert muncul (script dijalankan): {$teks}");
        } catch (NoSuchAlertException) {
            $this->addToAssertionCount(1);
        }
    }

    // ------------------------------------------------------------------
    // Pembacaan database (verifikasi tambahan, TIDAK membuat data)
    // ------------------------------------------------------------------

    protected function idProfilKandidat(string $email): ?int
    {
        $id = DB::table('candidate_profiles')
            ->join('users', 'users.id', '=', 'candidate_profiles.user_id')
            ->where('users.email', mb_strtolower($email))
            ->value('candidate_profiles.id');

        return $id === null ? null : (int) $id;
    }

    protected function lamaranDb(string $emailKandidat, int $proyekId): ?object
    {
        return DB::table('applications')
            ->where('project_id', $proyekId)
            ->where('candidate_profile_id', $this->idProfilKandidat($emailKandidat) ?? 0)
            ->first();
    }

    protected function jumlahLamaranProyek(int $proyekId): int
    {
        return DB::table('applications')->where('project_id', $proyekId)->count();
    }

    protected function riwayatDb(int $lamaranId): array
    {
        return DB::table('application_status_histories')
            ->where('application_id', $lamaranId)
            ->orderBy('id')
            ->get()
            ->all();
    }

    protected function statusLamaranDb(int $lamaranId): ?string
    {
        return DB::table('applications')->where('id', $lamaranId)->value('status');
    }

    /** ID yang dijamin tidak ada di tabel (untuk skenario "tidak ditemukan"). */
    protected function idTidakAda(string $tabel, int $usulan): int
    {
        return DB::table($tabel)->where('id', $usulan)->exists()
            ? (int) DB::table($tabel)->max('id') + 100000
            : $usulan;
    }

    // ------------------------------------------------------------------
    // Simulasi kondisi yang BELUM bisa dibuat lewat UI (opt-in)
    // ------------------------------------------------------------------

    /**
     * Beberapa test case membutuhkan proyek berstatus Ditutup atau bertenggat hari ini/kemarin. Kondisi ini
     * tidak dapat dibuat lewat UI karena:
     *  - routes/web.php tidak memiliki fitur untuk menutup proyek, dan
     *  - ProjectRequest mewajibkan tenggat "after:today" (paling cepat besok).
     * Excel pun menulis langkahnya sebagai "(Penguji lain) ubah status/tenggat di database testing".
     *
     * Karena dosen melarang membuat data langsung di database, test ini DILEWATI secara default.
     * Proyeknya tetap dibuat lewat UI; yang disimulasikan hanya SATU perubahan status/tenggat pada proyek itu.
     * Aktifkan dengan DUSK_IZINKAN_SIMULASI_DB=true di .env.dusk.local bila dosen mengizinkan.
     */
    protected function butuhSimulasiDb(string $tc): void
    {
        if (! filter_var(env('DUSK_IZINKAN_SIMULASI_DB', false), FILTER_VALIDATE_BOOL)) {
            $this->markTestSkipped(
                "{$tc}: membutuhkan proyek Ditutup/bertenggat hari ini atau kemarin yang belum dapat dibuat lewat UI "
                .'(tidak ada fitur tutup proyek; tenggat wajib setelah hari ini). Set DUSK_IZINKAN_SIMULASI_DB=true untuk menjalankan.'
            );
        }
    }

    /** Satu-satunya penulisan ke database di seluruh test E2E. Hanya dipanggil setelah butuhSimulasiDb(). */
    protected function simulasikanKondisiProyek(int $proyekId, array $kolom): void
    {
        DB::table('projects')->where('id', $proyekId)->update($kolom);
    }

    // ------------------------------------------------------------------
    // Utilitas
    // ------------------------------------------------------------------

    protected function tanggalIndonesia(Carbon|string|null $tanggal = null, string $format = 'j M Y'): string
    {
        return Carbon::parse($tanggal ?? now())->locale('id')->translatedFormat($format);
    }

    private function idDariUrl(Browser $browser, string $pola): int
    {
        if (! preg_match($pola, $browser->driver->getCurrentURL(), $m)) {
            $this->fail('ID tidak ditemukan pada URL: '.$browser->driver->getCurrentURL());
        }

        return (int) $m[1];
    }

    private function halamanMemuat(Browser $browser, string $teks): bool
    {
        return (bool) $browser->driver->executeScript('return document.body.innerText.includes(arguments[0]);', [$teks]);
    }

    /** Literal XPath yang aman untuk teks berisi tanda kutip. */
    protected function xpathLiteral(string $teks): string
    {
        if (! str_contains($teks, "'")) {
            return "'{$teks}'";
        }

        return 'concat(\''.str_replace("'", "',\"'\",'", $teks).'\')';
    }
}
