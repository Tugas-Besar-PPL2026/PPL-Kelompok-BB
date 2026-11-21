<?php

namespace Tests\Browser\Habib\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;

/**
 * Helper E2E Habib untuk PBI-03 (Keterampilan), PBI-04 (Pengalaman Kerja), dan PBI-05 (Portofolio).
 *
 * Dipakai bersama trait tim Tests\Browser\Concerns\AlurE2EProyekKita (login/registrasi/keluar lewat UI,
 * kodeUnik(), kirimFormManipulasi(), assertHalamanGalat(), dll.), sehingga prinsipnya sama dengan test
 * Shahira PBI-16/17/18/43/44/45:
 *  - Semua data skenario dibuat lewat UI di Chrome (registrasi, Data Diri, keterampilan, pengalaman,
 *    portofolio, master data oleh Admin). Tidak ada factory, seeder, SQL INSERT/UPDATE, atau loginAs().
 *  - Database hanya DIBACA (DB::table) untuk verifikasi tambahan setelah aksi di browser.
 *  - Setiap test memakai akun kandidat baru dengan email unik, sehingga test independen dan dapat diulang
 *    pada database test persistent. Data bersama yang dipakai ulang hanya master keterampilan khusus Dusk.
 *  - Berkas uji (PDF valid dan berkas .exe tiruan) dibuat saat test berjalan di storage/framework/testing
 *    lalu dihapus di tearDown. Tidak memakai berkas pribadi.
 *
 * Selector diambil dari Blade aktual:
 *  - resources/views/kandidat/profil/_keterampilan.blade.php
 *  - resources/views/kandidat/profil/_pengalaman.blade.php
 *  - resources/views/kandidat/profil/_portofolio.blade.php (+ portofolio.blade.php, _samping.blade.php)
 */
trait ProfilKandidatE2E
{
    // ------------------------------------------------------------------
    // Master keterampilan khusus test Habib (dibuat Admin lewat UI bila belum ada)
    // ------------------------------------------------------------------

    /** Keterampilan aktif pada Excel ("Figma"). Diberi awalan Dusk agar tidak bentrok dengan data lain. */
    protected const SKILL_FIGMA = 'Dusk Figma';

    /** Keterampilan aktif kedua (keterampilan B pada TC-PBI03-006). */
    protected const SKILL_COPYWRITING = 'Dusk Copywriting';

    /** Keterampilan pada data master yang berstatus Nonaktif (TC-PBI03-005). */
    protected const SKILL_NONAKTIF = 'Dusk Keterampilan Nonaktif';

    // Pesan yang benar-benar dipakai aplikasi (controller, FormRequest, Blade).
    protected const MSG_SKILL_DITAMBAH = 'Keterampilan berhasil ditambahkan.';

    protected const MSG_SKILL_DIHAPUS = 'Keterampilan berhasil dihapus.';

    protected const MSG_SKILL_SUDAH_ADA = 'Keterampilan sudah dipilih sebelumnya.';

    protected const MSG_SKILL_TIDAK_TERSEDIA = 'Keterampilan yang dipilih tidak tersedia atau sudah dinonaktifkan.';

    protected const MSG_SKILL_KOSONG = 'Belum ada keterampilan yang dipilih.';

    protected const MSG_EXP_DITAMBAH = 'Pengalaman kerja berhasil ditambahkan.';

    protected const MSG_EXP_DIUBAH = 'Pengalaman kerja berhasil diperbarui.';

    protected const MSG_EXP_DIHAPUS = 'Pengalaman kerja berhasil dihapus.';

    protected const MSG_EXP_KOSONG = 'Belum ada pengalaman kerja yang ditambahkan.';

    protected const MSG_PF_DITAMBAH = 'Portofolio berhasil ditambahkan.';

    protected const MSG_PF_DIUBAH = 'Portofolio berhasil diperbarui.';

    protected const MSG_PF_DIHAPUS = 'Portofolio berhasil dihapus.';

    protected const MSG_PF_URL_TIDAK_VALID = 'Tautan portofolio harus berupa URL http/https yang valid.';

    protected const MSG_PF_TAUTAN_ATAU_DOKUMEN = 'Isi minimal salah satu: tautan atau dokumen.';

    protected const MSG_PF_FORMAT_BERKAS = 'Dokumen portofolio harus berformat PDF, JPG, atau PNG.';

    private static bool $masterKeterampilanSiap = false;

    /** @var list<string> berkas uji yang dibuat test ini (dihapus di tearDown) */
    private array $berkasUji = [];

    // ------------------------------------------------------------------
    // Master data
    // ------------------------------------------------------------------

    /**
     * Memastikan SKILL_FIGMA dan SKILL_COPYWRITING aktif serta SKILL_NONAKTIF nonaktif pada data master.
     * Pemeriksaan awal hanya membaca database; bila belum sesuai, Admin membuat/mengubahnya lewat halaman
     * Data Master (/admin/data-master). Diakhiri tanpa sesi login.
     */
    protected function pastikanMasterKeterampilan(Browser $browser): void
    {
        if (self::$masterKeterampilanSiap) {
            return;
        }

        $target = [self::SKILL_FIGMA => true, self::SKILL_COPYWRITING => true, self::SKILL_NONAKTIF => false];

        $perluAdmin = collect($target)->contains(function (bool $aktif, string $nama) {
            $skill = $this->skillDb($nama);

            return $skill === null || (bool) $skill->is_active !== $aktif;
        });

        if ($perluAdmin) {
            $this->masukSebagaiAdmin($browser);

            foreach ($target as $nama => $aktif) {
                $browser->visit('/admin/data-master')->waitFor('@kartu-keterampilan');

                if ($this->skillDb($nama) === null) {
                    $this->tandaiHalamanLama($browser);
                    $browser->type('@input-keterampilan-baru', $nama)->press('@tambah-keterampilan');
                    $this->tungguHalamanBaru($browser)->assertSee('Keterampilan baru berhasil ditambahkan.');
                }

                $skill = $this->skillDb($nama);
                if ((bool) $skill->is_active !== $aktif) {
                    $this->tandaiHalamanLama($browser);
                    $browser->click("@toggle-keterampilan-{$skill->id}");
                    $this->tungguHalamanBaru($browser)
                        ->assertSeeIn("@status-keterampilan-{$skill->id}", $aktif ? 'Aktif' : 'Nonaktif');
                }
            }

            $this->keluar($browser);
        }

        self::$masterKeterampilanSiap = true;
    }

    // ------------------------------------------------------------------
    // Persiapan kandidat (seluruhnya lewat UI)
    // ------------------------------------------------------------------

    /**
     * Kandidat baru dengan profil (Data Diri tersimpan), lalu - bila diminta - keterampilan, pengalaman,
     * dan portofolio ditambahkan lewat UI. Profil kandidat baru dibuat aplikasi saat Data Diri disimpan;
     * tanpa profil, aksi keterampilan/pengalaman/portofolio ditolak (403 "Lengkapi data diri terlebih dahulu.").
     * Diakhiri tanpa sesi login.
     *
     * @param  array{keterampilan?: list<string>, pengalaman?: list<array{0:string,1:string,2:string}>, portofolio?: list<array{0:string,1:string,2:string}>}  $isi
     * @return array{nama: string, email: string, sandi: string}
     */
    protected function siapkanKandidatProfil(Browser $browser, string $nama = 'Budi Santoso', array $isi = []): array
    {
        $this->pastikanMasterKeterampilan($browser);

        $kode = $this->kodeUnik();
        $slugNama = Str::slug($nama, '.') ?: 'pengguna';
        $akun = [
            'nama' => $nama,
            'email' => "{$slugNama}.{$kode}@proyekkita.test",
            'sandi' => self::SANDI_E2E,
        ];

        $this->daftar($browser, 'kandidat', $akun['nama'], $akun['email'], $akun['sandi']);
        $this->isiDataDiri($browser, $akun['nama']);

        foreach ($isi['keterampilan'] ?? [] as $nama) {
            $this->tambahKeterampilanUi($browser, $nama);
        }

        foreach ($isi['pengalaman'] ?? [] as [$bidang, $durasi, $deskripsi]) {
            $this->tambahPengalamanUi($browser, $bidang, $durasi, $deskripsi);
        }

        if (! empty($isi['portofolio'])) {
            $this->bukaTabPortofolio($browser);
            foreach ($isi['portofolio'] as [$judul, $deskripsi, $tautan]) {
                $this->isiFormPortofolio($browser, $judul, $deskripsi, $tautan);
                $this->simpanPortofolio($browser)->assertSee(self::MSG_PF_DITAMBAH);
            }
        }

        $this->keluar($browser);

        return $akun;
    }

    // ------------------------------------------------------------------
    // Aksi UI: Keterampilan (tab "Keterampilan & Pengalaman", /kandidat/profil)
    // ------------------------------------------------------------------

    protected function bukaTabKeterampilanPengalaman(Browser $browser): Browser
    {
        return $browser->visit('/kandidat/profil')
            ->waitFor('#bagian-keterampilan')
            ->assertSeeIn('.tabs a.on', 'Keterampilan & Pengalaman');
    }

    /** Cari keterampilan pada kolom "Tambah keterampilan" lalu klik "+ tambah" pada baris yang sesuai. */
    protected function tambahKeterampilanUi(Browser $browser, string $nama): Browser
    {
        $this->bukaTabKeterampilanPengalaman($browser)
            ->type('#cari-keterampilan', $nama)
            ->waitFor($this->barisSkill($nama).' .skill-add');

        $this->tandaiHalamanLama($browser);
        $browser->click($this->barisSkill($nama).' .skill-add');

        return $this->tungguHalamanBaru($browser)->assertSee(self::MSG_SKILL_DITAMBAH);
    }

    /** Klik tombol x (aria-label "Hapus {nama}") pada tag keterampilan terpilih. */
    protected function hapusKeterampilanUi(Browser $browser, string $nama): Browser
    {
        $this->tandaiHalamanLama($browser);
        $this->klikTombolIkon($browser, '#selected-skills button[aria-label="Hapus '.$nama.'"]');

        return $this->tungguHalamanBaru($browser)->assertSee(self::MSG_SKILL_DIHAPUS);
    }

    /**
     * Klik tombol yang isinya hanya ikon Lucide (x, pensil, tempat sampah).
     * Ikon dimuat dari CDN unpkg.com (layouts/app.blade.php). Bila CDN tidak terjangkau (mis. komputer/CI tanpa
     * internet) ikon tidak dirender dan tombol tanpa padding (tag-rm) berukuran 0x0 sehingga WebDriver menolak klik.
     * Dalam kondisi itu saja klik dikirim lewat element.click() pada tombol yang sama (form tetap dikirim normal).
     */
    protected function klikTombolIkon(Browser $browser, string $selector): Browser
    {
        $browser->waitFor($selector);
        $ukuran = $browser->driver->executeScript(
            'const r = document.querySelector(arguments[0]).getBoundingClientRect(); return r.width * r.height;',
            [$selector]
        );

        if ((float) $ukuran > 0) {
            return $browser->click($selector);
        }

        $browser->driver->executeScript('document.querySelector(arguments[0]).click();', [$selector]);

        return $browser;
    }

    protected function barisSkill(string $nama): string
    {
        return '#skill-list [data-skill-name="'.mb_strtolower($nama).'"]';
    }

    /** Nama keterampilan terpilih yang tampil pada #selected-skills (urut tampilan). */
    protected function keterampilanTampil(Browser $browser): array
    {
        return $browser->driver->executeScript(
            "return [...document.querySelectorAll('#selected-skills .tag.on > span')].map(s => s.textContent.trim());"
        );
    }

    /** Apakah baris keterampilan pada daftar pilihan terlihat (tidak tersaring/tersembunyi). */
    protected function barisSkillTerlihat(Browser $browser, string $nama): bool
    {
        return (bool) $browser->driver->executeScript(
            'const el = document.querySelector(arguments[0]); return !!el && el.offsetParent !== null;',
            [$this->barisSkill($nama)]
        );
    }

    // ------------------------------------------------------------------
    // Aksi UI: Pengalaman Kerja (#form-pengalaman pada /kandidat/profil)
    // ------------------------------------------------------------------

    protected function isiFormPengalaman(Browser $browser, string $bidang, string $durasi, string $deskripsi): Browser
    {
        return $browser->waitFor('#form-pengalaman')
            ->clear('#work_type')->type('#work_type', $bidang)
            ->clear('#duration')->type('#duration', $durasi)
            ->clear('#exp_description')->type('#exp_description', $deskripsi);
    }

    /** Klik tombol simpan form pengalaman lalu tunggu halaman hasil (sukses atau galat validasi). */
    protected function simpanPengalaman(Browser $browser): Browser
    {
        $this->tandaiHalamanLama($browser);
        $browser->script("document.getElementById('btn-submit-exp').scrollIntoView({block: 'center'});");
        $browser->click('#btn-submit-exp');

        return $this->tungguHalamanBaru($browser);
    }

    protected function tambahPengalamanUi(Browser $browser, string $bidang, string $durasi, string $deskripsi): Browser
    {
        $this->bukaTabKeterampilanPengalaman($browser)->assertSeeIn('#exp-form-title', 'Tambah pengalaman');
        $this->isiFormPengalaman($browser, $bidang, $durasi, $deskripsi);

        return $this->simpanPengalaman($browser)->assertSee(self::MSG_EXP_DITAMBAH);
    }

    /** Isi kartu pengalaman yang tampil: list of [bidang, durasi, deskripsi]. */
    protected function pengalamanTampil(Browser $browser): array
    {
        return $browser->driver->executeScript(
            "return [...document.querySelectorAll('#experience-list .exp-item')].map(it => ["
            ."it.querySelector('.exp-body .b')?.textContent.trim() ?? '',"
            ."it.querySelector('.exp-body .bd')?.textContent.trim() ?? '',"
            ."it.querySelector('.exp-body .exp-desc')?.textContent.trim() ?? '']);"
        );
    }

    /** Pesan galat validasi pada field form pengalaman/portofolio (elemen .hint.err di dalam .fl yang sama). */
    protected function galatField(Browser $browser, string $idInput): ?string
    {
        return $browser->driver->executeScript(
            "const fl = document.getElementById(arguments[0])?.closest('.fl');"
            ."const err = fl ? fl.querySelector('.hint.err') : null; return err ? err.textContent.trim() : null;",
            [$idInput]
        );
    }

    // ------------------------------------------------------------------
    // Aksi UI: Portofolio (tab "Portofolio", /kandidat/profil/portofolio)
    // ------------------------------------------------------------------

    /** Klik tab "Portofolio" pada halaman Profil & Portofolio. */
    protected function bukaTabPortofolio(Browser $browser): Browser
    {
        if (parse_url($browser->driver->getCurrentURL(), PHP_URL_PATH) !== '/kandidat/profil') {
            $this->bukaTabKeterampilanPengalaman($browser);
        }

        $this->tandaiHalamanLama($browser);
        $browser->click('.tabs a[href$="/kandidat/profil/portofolio"]');

        return $this->tungguHalamanBaru($browser)
            ->assertPathIs('/kandidat/profil/portofolio')
            ->assertSeeIn('.tabs a.on', 'Portofolio')
            ->waitFor('#form-portofolio');
    }

    protected function isiFormPortofolio(Browser $browser, string $judul, string $deskripsi, string $tautan): Browser
    {
        return $browser->waitFor('#form-portofolio')
            ->clear('#pf_title')->type('#pf_title', $judul)
            ->clear('#pf_description')->type('#pf_description', $deskripsi)
            ->clear('#pf_link')->type('#pf_link', $tautan);
    }

    protected function simpanPortofolio(Browser $browser): Browser
    {
        $this->tandaiHalamanLama($browser);
        $browser->script("document.getElementById('btn-submit-pf').scrollIntoView({block: 'center'});");
        $browser->click('#btn-submit-pf');

        return $this->tungguHalamanBaru($browser);
    }

    /**
     * Memilih berkas pada input "Dokumen portofolio". Input aslinya disembunyikan (display:none) dan dibuka lewat
     * area seret/pilih file; dialog file bawaan OS tidak dapat dikendalikan WebDriver, sehingga input dibuat
     * terlihat sementara lalu berkas dikirim langsung ke input tersebut (cara standar unggah berkas di Dusk).
     */
    protected function pilihBerkasPortofolio(Browser $browser, string $path): Browser
    {
        $browser->script("document.getElementById('pf_file').style.display = 'block';");
        $browser->attach('#pf_file', $path);
        $browser->script("document.getElementById('pf_file').style.display = 'none';");

        return $browser->waitForTextIn('#pf-file-label', basename($path));
    }

    /** Isi kartu portofolio yang tampil: list of ['judul','deskripsi','tautan','href','berkas','href_berkas']. */
    protected function portofolioTampil(Browser $browser): array
    {
        $kunci = ['judul', 'deskripsi', 'tautan', 'href', 'berkas', 'href_berkas'];

        // WebDriver tidak menjamin urutan kunci objek JS; urutkan ulang agar dapat dibandingkan dengan assertSame.
        return array_map(
            fn (array $kartu) => array_combine($kunci, array_map(fn ($k) => $kartu[$k] ?? null, $kunci)),
            $this->kartuPortofolioMentah($browser)
        );
    }

    private function kartuPortofolioMentah(Browser $browser): array
    {
        return $browser->driver->executeScript(<<<'JS'
            return [...document.querySelectorAll('#portfolio-list article.pf-card')].map(c => {
                const link = c.querySelector('.pf-meta > a.pf-link');
                const berkas = c.querySelector('.pf-meta div.pf-link');
                const berkasA = berkas ? berkas.querySelector('a') : null;
                return {
                    judul: c.querySelector('.pf-title')?.textContent.trim() ?? '',
                    deskripsi: c.querySelector('.pf-desc')?.textContent.trim() ?? '',
                    tautan: link ? link.textContent.trim() : null,
                    href: link ? link.getAttribute('href') : null,
                    berkas: berkas ? berkas.textContent.replace(/\s+/g, ' ').trim() : null,
                    href_berkas: berkasA ? berkasA.getAttribute('href') : null,
                };
            });
        JS);
    }

    // ------------------------------------------------------------------
    // Simulasi kegagalan penyimpanan dari browser
    // ------------------------------------------------------------------

    /**
     * Mensimulasikan kegagalan proses simpan di sisi server tanpa mengubah aplikasi: token CSRF pada form
     * halaman diganti dengan nilai tidak valid (setara sesi kedaluwarsa / request ditolak server), lalu
     * pengguna menekan tombol simpan seperti biasa. Server menolak request (HTTP 419 Page Expired).
     */
    protected function rusakTokenCsrf(Browser $browser): Browser
    {
        $browser->script(
            "document.querySelectorAll('input[name=\"_token\"]').forEach(i => i.value = 'token-csrf-tidak-valid-dusk');"
        );

        return $browser;
    }

    // ------------------------------------------------------------------
    // Berkas uji (dibuat saat test berjalan, dihapus di tearDown)
    // ------------------------------------------------------------------

    /** PDF 1 halaman yang valid (struktur PDF minimal dengan tabel xref yang benar). */
    protected function buatPdfUji(string $nama = 'portofolio-uji-dusk.pdf'): string
    {
        $objek = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 300 144] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            null, // stream diisi di bawah
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];
        $isi = "BT /F1 14 Tf 20 70 Td (Portofolio uji Dusk) Tj ET";
        $objek[3] = '<< /Length '.strlen($isi)." >>\nstream\n{$isi}\nendstream";

        $pdf = "%PDF-1.4\n";
        $offset = [];
        foreach ($objek as $i => $o) {
            $offset[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n{$o}\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objek) + 1)."\n0000000000 65535 f \n";
        foreach ($offset as $o) {
            $pdf .= sprintf("%010d 00000 n \n", $o);
        }
        $pdf .= "trailer\n<< /Size ".(count($objek) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";

        return $this->tulisBerkasUji($nama, $pdf);
    }

    /** Berkas berekstensi .exe (format yang tidak didukung). Isinya teks biasa, tidak dapat dieksekusi. */
    protected function buatExeUji(string $nama = 'program-uji-dusk.exe'): string
    {
        return $this->tulisBerkasUji($nama, "MZ - berkas tiruan untuk pengujian format tidak didukung (bukan program).\n");
    }

    private function tulisBerkasUji(string $nama, string $isi): string
    {
        $dir = storage_path('framework/testing/dusk-habib/'.$this->kodeUnik());
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $path = $dir.DIRECTORY_SEPARATOR.$nama;
        file_put_contents($path, $isi);
        $this->berkasUji[] = $path;

        return $path;
    }

    /** Dipanggil otomatis oleh Laravel (tearDown + nama trait): hapus berkas uji yang dibuat test ini. */
    public function tearDownProfilKandidatE2E(): void
    {
        foreach ($this->berkasUji as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
            @rmdir(dirname($path));
            @rmdir(dirname($path, 2)); // folder dusk-habib, hanya terhapus bila sudah kosong
        }

        $this->berkasUji = [];
    }

    // ------------------------------------------------------------------
    // Pembacaan database (verifikasi tambahan, TIDAK menulis data)
    // ------------------------------------------------------------------

    protected function skillDb(string $nama): ?object
    {
        return DB::table('skills')->whereRaw('LOWER(name) = ?', [mb_strtolower($nama)])->first();
    }

    /** Nama keterampilan pada profil kandidat, urut skills.id (urutan yang sama dengan tampilan). */
    protected function keterampilanDb(string $email): array
    {
        return DB::table('candidate_skill')
            ->join('skills', 'skills.id', '=', 'candidate_skill.skill_id')
            ->where('candidate_skill.candidate_profile_id', $this->idProfilKandidat($email) ?? 0)
            ->orderBy('skills.id')
            ->pluck('skills.name')
            ->all();
    }

    protected function pengalamanDb(string $email): array
    {
        return DB::table('experiences')
            ->where('candidate_profile_id', $this->idProfilKandidat($email) ?? 0)
            ->orderBy('id')
            ->get()
            ->all();
    }

    protected function portofolioDb(string $email): array
    {
        return DB::table('portfolios')
            ->where('candidate_profile_id', $this->idProfilKandidat($email) ?? 0)
            ->orderBy('id')
            ->get()
            ->all();
    }
}
