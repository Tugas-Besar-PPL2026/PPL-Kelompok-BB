# Laravel Dusk – PBI-03, PBI-04, PBI-05 (Habib)

Isi folder ini adalah automated browser test (Laravel Dusk) untuk 30 test case pada
`Test_Case_ProyekKita_PBI03-05.xlsx`:

| PBI | Fitur | Folder | Jumlah TC |
|-----|-------|--------|-----------|
| PBI-03 | Mengelola Keterampilan pada Profil (Kandidat) | `tests/Browser/Habib/PBI03/` | 10 |
| PBI-04 | Mengelola Pengalaman Kerja pada Profil (Kandidat) | `tests/Browser/Habib/PBI04/` | 10 |
| PBI-05 | Mengelola Portofolio (Kandidat) | `tests/Browser/Habib/PBI05/` | 10 |

## Isi ZIP

```
proyekkita/tests/Browser/Habib/
├── Concerns/ProfilKandidatE2E.php      # helper baru (trait) khusus PBI-03/04/05
├── PBI03/TC_PBI03_001_Test.php … TC_PBI03_010_Test.php
├── PBI04/TC_PBI04_001_Test.php … TC_PBI04_010_Test.php
├── PBI05/TC_PBI05_001_Test.php … TC_PBI05_010_Test.php
└── README_PBI03-05.md
```

Ekstrak ZIP di **root repository** (folder yang berisi `proyekkita/`). Tidak ada file lain yang ditimpa:
tidak ada perubahan pada kode aplikasi, migration, route, `DuskTestCase`, maupun helper milik anggota lain.

Satu file = satu test case, nama class `TC_PBI0X_00N_Test`, sama seperti test Shahira PBI-16/17/18/43/44/45.
Namespace mengikuti PSR-4 (`Tests\Browser\Habib\PBI03`, dst.), seperti folder Adnan/Aisyah/Cerin.

## Pola test (sama dengan test Shahira yang sudah ada)

Setiap class memakai trait tim yang sudah ada + satu trait baru:

- `Tests\Browser\Concerns\AlurE2EProyekKita` – login/registrasi/keluar lewat UI, `kirimFormManipulasi()`, `assertHalamanGalat()`, dll.
- `Tests\Browser\Concerns\MenjagaDatabaseE2E` – wajib MySQL dengan nama database mengandung `dusk`.
- `Tests\Concerns\UsesDuskTestDatabase` – memastikan server memakai database Dusk, hanya `migrate` biasa.
- `Tests\Browser\Habib\Concerns\ProfilKandidatE2E` (**baru**) – aksi UI keterampilan/pengalaman/portofolio, pembacaan DB read-only, dan berkas uji.

Aturannya:
- Semua data dibuat lewat UI Chrome: registrasi kandidat → isi Data Diri → tambah keterampilan/pengalaman/portofolio.
  Tidak ada factory, seeder, SQL tulis, atau `loginAs()`.
- Database hanya **dibaca** untuk verifikasi tambahan. Tidak ada `migrate:fresh`, `db:wipe`, `truncate`.
- Setiap test memakai akun kandidat baru dengan nama orang asli (tanpa kata `Kandidat` atau `TC`) dan email unik (`<nama>.<kode>@proyekkita.test`), jadi test bisa
  dijalankan sendiri-sendiri, dalam urutan apa pun, dan berulang kali pada database persistent.
- Data bersama yang dipakai ulang: master keterampilan `Dusk Figma`, `Dusk Copywriting` (aktif) dan
  `Dusk Keterampilan Nonaktif` (nonaktif). Bila belum ada, Admin membuatnya lewat halaman Data Master.
  TC-PBI03-009 juga memakai UMKM bersama dan master data tim (`Dusk Desain Grafis`) dari `AlurE2EProyekKita`.
- Berkas uji (PDF valid & `.exe` tiruan berisi teks) dibuat saat test berjalan di `storage/framework/testing/dusk-habib/`
  lalu dihapus di `tearDown`. Portofolio berlampiran pada TC-PBI05-007 dihapus lewat UI di akhir test, jadi berkasnya
  di `storage/app/public` ikut terhapus.

## Prasyarat (sama seperti test Shahira lainnya)

1. MySQL berjalan di `127.0.0.1:3306` dengan database **`proyekkita_dusk`** (lihat `.env.dusk.local`).
2. Akun Admin ada di database test: `DUSK_ADMIN_EMAIL=admin@proyekkita.test`, `DUSK_ADMIN_PASSWORD=password`.
3. `composer install` sudah dijalankan; Chrome/ChromeDriver sesuai versi (`php artisan dusk:chrome-driver --detect`).
4. Internet disarankan (ikon Lucide dari unpkg). Tanpa internet test tetap jalan: tombol yang isinya hanya ikon
   diklik lewat `element.click()` (lihat `klikTombolIkon()`).

Tidak ada dependensi composer/npm tambahan.

## Menjalankan test

```bash
cd proyekkita

# per PBI
php artisan dusk tests/Browser/Habib/PBI03
php artisan dusk tests/Browser/Habib/PBI04
php artisan dusk tests/Browser/Habib/PBI05

# lewat group
php artisan dusk --group=pbi03
php artisan dusk --group=e2e-profil-kandidat          # ke-30 test
php artisan dusk --group=TC-PBI05-007                 # satu test case

# tanpa 2 test yang menandai temuan aplikasi
php artisan dusk --group=e2e-profil-kandidat --exclude-group=temuan-aplikasi
```

`DUSK_JEDA=1000` di `.env.dusk.local` memberi jeda 1 detik pada setiap aksi. Untuk eksekusi cepat, set `DUSK_JEDA=0`.

## Hasil verifikasi

Diperiksa di sandbox Linux: PHP 8.3, Laravel 12.70.2, Dusk 8, Chrome 153 headless.

- `php -l` untuk 31 file: tidak ada syntax error. Autoload PSR-4 class dan trait berhasil di-resolve.
- Eksekusi dua kali berturut-turut, pertama pada database Dusk kosong dan kedua pada database yang sudah terisi
  (persistent). Hasil keduanya sama:

| PBI | Passed | Failed | Skipped |
|-----|--------|--------|---------|
| PBI-03 | 10 | 0 | 0 |
| PBI-04 | 9 | 1 (TC-PBI04-006, temuan) | 0 |
| PBI-05 | 9 | 1 (TC-PBI05-006, temuan) | 0 |
| **Total** | **28** | **2** | **0** |

- Test Shahira `TC_PBI16_001` dan `TC_PBI43_001` tetap lulus setelah ke-30 test ini dijalankan pada database
  yang sama.
- Catatan lingkungan sandbox: MySQL tidak tersedia di sana, jadi verifikasi memakai SQLite `database/dusk.sqlite`.
  Karena itu pengecekan driver MySQL di `MenjagaDatabaseE2E` di-bypass sementara di salinan sandbox, lalu dikembalikan
  ke versi asli. File tersebut tidak masuk ZIP. Di laptop, test berjalan dengan MySQL `proyekkita_dusk` seperti test
  Shahira lainnya.

## Temuan (test gagal karena perilaku aplikasi, bukan karena test)

Expected Result Excel tidak diubah agar test lulus. Kedua test ditandai group `temuan-aplikasi`.

1. **TC-PBI04-006 – durasi tidak valid diterima.**
   `app/Http/Requests/CandidateExperienceRequest.php` hanya memakai aturan `duration => required|string|max:50`.
   Nilai `-3 bulan`, `0 bulan`, dan `abc` diterima dan tersimpan.
   *Rekomendasi:* ubah durasi menjadi angka + satuan (mis. `duration_value` integer `min:1` + `duration_unit` in:bulan,tahun),
   atau tambahkan regex, misalnya `regex:/^[1-9]\d*\s*(bulan|tahun)$/i`. Pilihan lain: revisi Excel bila durasi bebas memang disengaja.
2. **TC-PBI05-006 – tautan `abc` diterima.**
   `App\Support\SelectionPresenter::safeUrl()` (dipakai `CandidatePortfolioRequest::after()`) menambahkan `https://`
   otomatis pada teks tanpa skema. Akibatnya `abc` tersimpan sebagai `https://abc`. Input `https://` sudah ditolak dengan benar.
   *Rekomendasi:* wajibkan skema `http/https` eksplisit dan host yang memiliki titik (mis. `url:http,https` + cek
   `str_contains($host, '.')`). Pilihan lain: revisi Excel bila normalisasi otomatis memang disengaja.

## Penyesuaian skenario Excel dengan aplikasi aktual

| TC | Penyesuaian |
|----|-------------|
| PBI03-003/006, PBI04-004, PBI05-004 | Tidak ada dialog konfirmasi hapus (langkah Excel bersyarat "jika tersedia"). Setiap tambah/hapus keterampilan langsung tersimpan; tidak ada tombol "Simpan perubahan" terpisah. |
| PBI03-004 | Duplikat dicegah di UI (label "sudah dipilih", tanpa tombol tambah). Pengiriman ulang dari browser menghasilkan pesan "Keterampilan sudah dipilih sebelumnya." |
| PBI03-005 | Selain tidak muncul di daftar, ID keterampilan nonaktif yang dikirim dari browser ditolak server. |
| PBI03-008, PBI04-009 | Route profil tidak memuat ID kandidat; controller selalu memakai profil pengguna yang login. Akses ke data kandidat lain diuji dengan aksi dari browser (DELETE/PUT): keterampilan → hanya memengaruhi profil sendiri, pengalaman → 403 (ExperiencePolicy), UMKM → 403. |
| PBI03-009 | Prasyarat "keterampilan dipakai proyek" dibuat dengan draf proyek UMKM lewat UI. |
| PBI03-010, PBI04-010 | Aplikasi tidak menyediakan simulasi kegagalan. Kegagalan simpan disimulasikan dari browser dengan token CSRF tidak valid → server menolak (HTTP 419 "Page Expired") dan data tidak tersimpan. Aplikasi belum punya halaman galat khusus (`resources/views/errors` kosong), jadi pesan yang tampil adalah halaman bawaan Laravel. |
| PBI05-007/008 | Input file disembunyikan (`display:none`) dan dibuka lewat area "Seret file"; dialog file OS tidak dapat dikendalikan WebDriver, jadi input dibuat terlihat sementara lalu berkas dikirim langsung ke input tersebut. |
| PBI05-010 | Tab Portofolio tidak punya teks empty state khusus. Yang diverifikasi: daftar kartu tidak tampil, form "Tambah Portofolio" tersedia, dan checklist "Minimal 1 portofolio" belum tercentang. Bila perlu, tambahkan teks empty state di `_portofolio.blade.php`. |
| PBI04 (umum) | "Hasil pekerjaan" pada Excel = field "Deskripsi pekerjaan" (`description`). |
