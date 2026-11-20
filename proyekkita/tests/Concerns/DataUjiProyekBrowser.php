<?php

namespace Tests\Concerns;

use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Enums\VerificationStatus;
use App\Models\Category;
use App\Models\Project;
use App\Models\Skill;
use App\Models\User;
use Laravel\Dusk\Browser;

/**
 * Data uji tetap untuk test Dusk PBI-9, PBI-10, dan PBI-11.
 *
 * Tidak memakai factory, seeder, atau nilai acak: setiap akun, data master, dan proyek awal ditulis lengkap
 * dengan nilai yang sama di setiap pengujian, sesuai kolom Precondition pada dokumen test case.
 * Isian yang diuji (judul, termin, pernyataan, dan seterusnya) diketik lewat form di browser oleh tiap test.
 */
trait DataUjiProyekBrowser
{
    /** Kata sandi semua akun uji. */
    protected function kataSandiUji(): string
    {
        return 'Password';
    }

    /** UMKM utama: Rina Kartika, pemilik Kopi Nusantara di Bandung, sudah terverifikasi. */
    protected function buatUmkmKopiNusantara(): User
    {
        return $this->buatAkunUmkm('Rina Kartika', 'rina@kopinusantara.test', [
            'business_name' => 'Kopi Nusantara',
            'description' => 'Roastery kopi lokal di Bandung yang menjual biji kopi Arabika Gayo, Robusta Temanggung, dan Liberika Riau.',
            'business_field' => 'Kuliner & Minuman',
            'contact_phone' => '081234567890',
            'city' => 'Bandung',
            'address' => 'Jl. Braga No. 21, Kecamatan Sumur Bandung, Kota Bandung',
            'nib' => '9120001234567',
        ], VerificationStatus::Verified);
    }

    /** UMKM yang verifikasinya masih menunggu admin: Andi Saputra, pemilik Roti Andi di Bekasi. */
    protected function buatUmkmRotiAndiBelumTerverifikasi(): User
    {
        return $this->buatAkunUmkm('Andi Saputra', 'andi@rotiandi.test', [
            'business_name' => 'Roti Andi',
            'description' => 'Toko roti rumahan di Bekasi yang menjual roti manis, roti tawar, dan kue kering.',
            'business_field' => 'Kuliner & Minuman',
            'contact_phone' => '083134567890',
            'city' => 'Bekasi',
            'address' => 'Jl. Ahmad Yani No. 8, Kecamatan Bekasi Selatan, Kota Bekasi',
            'nib' => null,
        ], VerificationStatus::Pending);
    }

    /** Membuat akun UMKM beserta profil usahanya. */
    protected function buatAkunUmkm(string $nama, string $email, array $profil, VerificationStatus $status): User
    {
        $user = User::create([
            'name' => $nama,
            'email' => $email,
            'password' => $this->kataSandiUji(),
            'role' => UserRole::Umkm,
        ]);

        $umkm = $user->umkmProfile()->create($profil + ['verification_status' => $status]);

        if ($status === VerificationStatus::Verified) {
            $umkm->forceFill(['verified_at' => now()->subDays(14)])->save();
        }

        return $user;
    }

    /** Dua akun admin yang menerima notifikasi pengajuan proyek. */
    protected function buatDuaAdmin(): array
    {
        return [
            User::create(['name' => 'Admin ProyekKita', 'email' => 'admin@proyekkita.test', 'password' => $this->kataSandiUji(), 'role' => UserRole::Admin]),
            User::create(['name' => 'Siti Rahmawati', 'email' => 'siti.rahmawati@proyekkita.test', 'password' => $this->kataSandiUji(), 'role' => UserRole::Admin]),
        ];
    }

    /** Data master aktif yang dipakai form proyek. */
    protected function siapkanDataMaster(): void
    {
        foreach (['Desain Grafis & Branding', 'Fotografi Produk', 'Pemasaran Digital'] as $nama) {
            Category::create(['name' => $nama, 'is_active' => true]);
        }

        foreach (['Adobe Illustrator', 'Copywriting', 'Desain Kemasan', 'Figma'] as $nama) {
            Skill::create(['name' => $nama, 'is_active' => true]);
        }
    }

    protected function kategori(string $nama): Category
    {
        return Category::where('name', $nama)->firstOrFail();
    }

    protected function keterampilan(string $nama): Skill
    {
        return Skill::where('name', $nama)->firstOrFail();
    }

    /**
     * Proyek awal (precondition) milik UMKM: label kemasan Kopi Arabika Gayo, total Rp3.000.000 dalam dua termin.
     * $isian menimpa kolom tertentu, $termin menimpa termin: [[nominal, kondisi], ...].
     */
    protected function buatProyekAwal(User $umkm, array $isian = [], ?array $termin = null, array $keterampilan = ['Desain Kemasan', 'Adobe Illustrator']): Project
    {
        $project = Project::create(array_merge([
            'umkm_profile_id' => $umkm->umkmProfile->id,
            'category_id' => $this->kategori('Desain Grafis & Branding')->id,
            'title' => 'Redesain Label Kemasan Kopi Arabika Gayo',
            'description' => 'Kopi Nusantara membutuhkan desainer untuk memperbarui label kemasan kopi Arabika Gayo ukuran 250 gram dan 1 kilogram agar tampil lebih modern di rak toko dan marketplace.',
            'expected_output' => "Desain label kemasan 250 gram dan 1 kilogram siap cetak (PDF dan AI)\nMockup kemasan untuk foto katalog marketplace",
            'application_deadline' => now()->addDays(10)->toDateString(),
            'target_completion_date' => now()->addDays(40)->toDateString(),
            'quota' => 2,
            'total_compensation' => 3000000,
            'status' => ProjectStatus::Draft,
            'published_at' => null,
        ], $isian));

        $project->skills()->sync(array_map(fn (string $nama) => $this->keterampilan($nama)->id, $keterampilan));

        $termin ??= [
            [1500000, 'Uang muka setelah kesepakatan kerja disetujui kedua pihak'],
            [1500000, 'Pelunasan setelah file desain final diserahkan'],
        ];

        foreach ($termin as $i => [$nominal, $kondisi]) {
            $project->paymentTerms()->create(['term_number' => $i + 1, 'amount' => $nominal, 'condition' => $kondisi]);
        }

        return $project;
    }

    /** Membuka halaman awal ProyekKita, klik Masuk, lalu mengisi email dan kata sandi seperti pengguna biasa. */
    protected function masuk(Browser $browser, User $user): Browser
    {
        $browser->driver->manage()->deleteAllCookies();

        return $browser->visit('/')
            ->clickAndWaitForReload('@nav-masuk')
            ->type('#email', $user->email)
            ->type('#password', $this->kataSandiUji())
            ->clickAndWaitForReload('@masuk');
    }

    /** Membuka halaman Proyek Saya lewat menu di sidebar. */
    protected function bukaProyekSaya(Browser $browser): Browser
    {
        return $browser->clickLink('Proyek Saya')->waitFor('@buat-proyek');
    }

    /** Membuka form Buat Proyek lewat menu Proyek Saya lalu tombol Buat Proyek. */
    protected function bukaFormBuatProyek(Browser $browser): Browser
    {
        return $this->bukaProyekSaya($browser)
            ->clickAndWaitForReload('@buat-proyek')
            ->assertPathIs('/umkm/proyek/buat');
    }

    /** Membuka form Ubah Draf lewat tombol Lanjutkan Draf pada kartu proyek di Proyek Saya. */
    protected function lanjutkanDraf(Browser $browser, Project $project): Browser
    {
        return $this->bukaProyekSaya($browser)
            ->clickAndWaitForReload('@lanjutkan-draf-' . $project->id)
            ->assertPathIs("/umkm/proyek/{$project->id}/ubah");
    }

    /** Membuka form Ubah Draf lewat tombol Perbaiki & Ajukan Ulang pada kartu proyek berstatus Perlu Perbaikan. */
    protected function perbaikiProyek(Browser $browser, Project $project): Browser
    {
        return $this->bukaProyekSaya($browser)
            ->clickAndWaitForReload('@perbaiki-' . $project->id)
            ->assertPathIs("/umkm/proyek/{$project->id}/ubah");
    }

    /**
     * Mengetik isian informasi proyek, jadwal, kuota, dan total kompensasi.
     * Nilai bawaan: katalog digital dan label kemasan Kopi Nusantara, total Rp2.500.000.
     */
    protected function isiInformasiProyek(Browser $browser, array $isian = []): Browser
    {
        $isian = array_merge([
            'judul' => 'Redesain Katalog Digital & Label Kemasan Kopi Nusantara',
            'kategori' => 'Desain Grafis & Branding',
            'keterampilan' => ['Desain Kemasan', 'Figma'],
            'deskripsi' => 'Kopi Nusantara membutuhkan desainer untuk memperbarui label kemasan tiga varian biji kopi (Arabika Gayo, Robusta Temanggung, Liberika Riau) dan menyusun katalog produk digital untuk reseller.',
            'hasil' => "Desain label kemasan 3 varian siap cetak (PDF dan AI)\nKatalog produk digital 8 halaman format PDF\nFile sumber Figma yang bisa diedit",
            'tenggat' => now()->addDays(7)->toDateString(),
            'target' => now()->addDays(30)->toDateString(),
            'kuota' => '2',
            'total' => '2500000',
        ], $isian);

        $browser->type('title', $isian['judul'])
            ->select('category_id', (string) $this->kategori($isian['kategori'])->id);

        foreach ($isian['keterampilan'] as $nama) {
            $browser->click('@skill-' . $this->keterampilan($nama)->id);
        }

        $browser->type('description', $isian['deskripsi'])
            ->type('expected_output', $isian['hasil']);

        // Kolom tanggal diisi lewat JavaScript karena cara mengetik tanggal berbeda-beda menurut bahasa browser.
        $browser->script([
            "document.getElementById('application_deadline').value = '{$isian['tenggat']}';",
            "document.getElementById('target_completion_date').value = '{$isian['target']}';",
        ]);

        return $browser->type('quota', $isian['kuota'])
            ->type('total_compensation', $isian['total']);
    }

    /** Mengisi kondisi pembayaran pada baris termin ke-$baris (mulai 1). */
    protected function isiKondisiTermin(Browser $browser, int $baris, string $kondisi): Browser
    {
        return $browser->type("#tabel-termin tbody tr:nth-child({$baris}) [name$=\"[condition]\"]", $kondisi);
    }

    /** Mengisi nominal pada baris termin ke-$baris (mulai 1). */
    protected function isiNominalTermin(Browser $browser, int $baris, string $nominal): Browser
    {
        return $browser->type("#tabel-termin tbody tr:nth-child({$baris}) .term-amount", $nominal);
    }

    /** Navbar menempel di atas halaman, jadi halaman digulung ke atas dulu sebelum menekan tombol di kepala halaman. */
    protected function tekan(Browser $browser, string $tombol): Browser
    {
        $browser->script('window.scrollTo(0, 0);');

        return $browser->press($tombol);
    }
}
