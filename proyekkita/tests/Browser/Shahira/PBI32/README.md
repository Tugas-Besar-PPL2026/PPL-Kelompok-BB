# PBI-32 — Dusk E2E: Meninjau Hasil Pekerjaan (UMKM)

Sumber test case: sheet **PBI-32** di `Test_Case_Kelompok_BB_SI4801_2.xlsx` (20 test case). Satu test case = satu file Dusk = satu method.

## Cara menjalankan

```bash
php artisan dusk --group=pbi32
php artisan dusk --group=TC-PBI32-001
```

Syarat lingkungan sama dengan PBI-31 (lihat `tests/Browser/Shahira/PBI31/README.md`): database MySQL testing terpisah, `DUSK_IZINKAN_SIMULASI_DB=true`, batas unggah server 30M/32M, hak membuat TRIGGER.

## Exception: setup database minimum

| Simulasi | Alasan | Dipakai di |
| --- | --- | --- |
| Baris `agreements` (termasuk `revision_limit` 0/1/2) untuk lamaran yang diterima lewat UI | PBI-24/25 belum diimplementasikan | Semua TC |
| Status kesepakatan menjadi Dibatalkan | PBI-28 belum ada | TC-009 |
| Penghapusan berkas dari disk | Simulasi berkas hilang dari penyimpanan | TC-015 |

Tidak ada simulasi status penyerahan atau revisi: Versi 1 (PBI-31), keputusan UMKM (PBI-32), dan versi perbaikan (PBI-33) seluruhnya dibuat lewat UI.

## Mapping test case → method Dusk → expected result

| ID Test Case | File / method Dusk | Expected result (Excel) |
| --- | --- | --- |
| TC-PBI32-001 | `TC_PBI32_001_Test::test_TC_PBI32_001_menyetujui_versi_1_tanpa_catatan` | Muncul pesan 'Hasil pekerjaan Versi 1 disetujui.'. Badge menjadi 'Versi 1 · Disetujui', panel keputusan menampilkan 'Hasil pekerjaan Versi 1 sudah disetujui.'. Data: status approved, reviewed_at terisi, review_note kosong, status kesepakatan tetap Aktif. Di halaman kandidat Versi 1 berstatus Disetujui dan muncul info 'Hasil pekerjaan sudah disetujui UMKM.'. |
| TC-PBI32-002 | `TC_PBI32_002_Test::test_TC_PBI32_002_menyetujui_versi_1_dengan_catatan` | Pesan sukses persetujuan tampil. Tabel Riwayat Versi baris Versi 1 menampilkan catatan tersebut. review_note tersimpan. Riwayat Versi kandidat menampilkan 'Catatan UMKM: Hasil sudah sesuai. ...'. |
| TC-PBI32-003 | `TC_PBI32_003_Test::test_TC_PBI32_003_meminta_revisi_versi_1_dengan_catatan` | Muncul pesan 'Permintaan revisi untuk Versi 1 berhasil dikirim kepada kandidat.'. Kuota menjadi 'Revisi terpakai 1 dari 2'; panel menampilkan 'Versi 1 sedang diperbaiki kandidat. Tunggu versi perbaikan untuk ditinjau.'. Data: status revision_requested, 1 baris revision_requests berisi catatan, reviewed_at terisi. Kandidat melihat banner 'Permintaan revisi dari ...' dan form 'Kirim Versi Perbaikan · Versi 2'. |
| TC-PBI32-004 | `TC_PBI32_004_Test::test_TC_PBI32_004_keputusan_tidak_dipilih_ditolak` | Ditolak dengan pesan 'Pilih keputusan peninjauan: setujui atau minta revisi.'. Catatan tetap terisi. Status Versi 1 tetap Menunggu Tinjauan. |
| TC-PBI32-005 | `TC_PBI32_005_Test::test_TC_PBI32_005_catatan_revisi_kosong_atau_spasi_ditolak` | A dan B ditolak dengan pesan 'Catatan perbaikan wajib diisi saat meminta revisi.'. Tidak ada revision_requests baru; status tetap Menunggu Tinjauan. |
| TC-PBI32-006 | `TC_PBI32_006_Test::test_TC_PBI32_006_batas_catatan_2001_ditolak_2000_diterima` | A ditolak dengan pesan 'Catatan maksimal 2000 karakter.' dan penghitung menunjukkan 2001/2000. B diterima: permintaan revisi tersimpan dengan catatan 2000 karakter. |
| TC-PBI32-007 | `TC_PBI32_007_Test::test_TC_PBI32_007_batas_revisi_habis_hanya_dapat_menyetujui` | Kuota 'Revisi terpakai 1 dari 1'; opsi 'Minta revisi' nonaktif dengan keterangan 'Batas revisi sudah habis. Hasil pekerjaan hanya dapat disetujui.'. Request langsung ditolak: 'Batas revisi sudah habis (1 dari 1). Hasil pekerjaan hanya dapat disetujui.'. Persetujuan tetap berhasil (Versi 2 Disetujui). |
| TC-PBI32-008 | `TC_PBI32_008_Test::test_TC_PBI32_008_batas_revisi_nol_opsi_revisi_nonaktif` | Kuota 'Revisi terpakai 0 dari 0'; opsi 'Minta revisi' nonaktif; hanya 'Setujui hasil pekerjaan' yang dapat dipilih. |
| TC-PBI32-009 | `TC_PBI32_009_Test::test_TC_PBI32_009_kesepakatan_tidak_aktif_menolak_keputusan` | A: form keputusan tidak tampil, info 'Kesepakatan berstatus Dibatalkan. Hasil pekerjaan hanya dapat ditinjau pada kesepakatan Aktif.'; request ditolak dengan pesan yang sama. B: ditolak dengan pesan yang sama. Status Versi 1 tetap Menunggu Tinjauan. |
| TC-PBI32-010 | `TC_PBI32_010_Test::test_TC_PBI32_010_tanpa_penyerahan_menampilkan_kondisi_kosong` | Panel menampilkan 'Kandidat belum menyerahkan hasil pekerjaan.'; Riwayat Versi 'Belum ada hasil pekerjaan yang diserahkan.'; form keputusan tidak tampil. |
| TC-PBI32-011 | `TC_PBI32_011_Test::test_TC_PBI32_011_versi_sudah_disetujui_atau_sedang_diperbaiki` | A: info dan penolakan 'Hasil pekerjaan Versi 1 sudah disetujui.'. B: info dan penolakan 'Versi 1 sedang diperbaiki kandidat. Tunggu versi perbaikan untuk ditinjau.'. Data tidak berubah. |
| TC-PBI32-012 | `TC_PBI32_012_Test::test_TC_PBI32_012_keputusan_ganda_dan_versi_berubah_ditolak` | A: keputusan pertama tersimpan (Disetujui); kiriman kedua ditolak 'Hasil pekerjaan Versi 1 sudah disetujui.' tanpa error 500; tidak ada revision_requests. B: ditolak 'Versi yang ditinjau sudah berubah. Muat ulang halaman lalu tinjau versi terbaru.'. |
| TC-PBI32-013 | `TC_PBI32_013_Test::test_TC_PBI32_013_keputusan_tidak_dikenal_dan_field_tambahan` | Request pertama ditolak 'Pilih keputusan peninjauan: setujui atau minta revisi.'. Request kedua tersimpan sebagai Versi 1 Disetujui pada kesepakatan uji; field tambahan diabaikan. |
| TC-PBI32-014 | `TC_PBI32_014_Test::test_TC_PBI32_014_umkm_membuka_berkas_pdf_zip_dan_tautan` | PDF: 200, Content-Disposition inline, X-Content-Type-Options: nosniff. ZIP: 200, Content-Disposition attachment. Tautan dibuka di tab baru (target _blank). |
| TC-PBI32-015 | `TC_PBI32_015_Test::test_TC_PBI32_015_akses_berkas_umkm_tidak_berhak` | Ketiganya 404; yang terakhir menampilkan 'Berkas hasil pekerjaan tidak ditemukan.'. |
| TC-PBI32-016 | `TC_PBI32_016_Test::test_TC_PBI32_016_hak_akses_halaman_tinjau` | Kandidat 403; Admin 403; tamu diarahkan ke /login; UMKM lain 404; ID tidak ada 404. Tidak ada keputusan tersimpan. |
| TC-PBI32-017 | `TC_PBI32_017_Test::test_TC_PBI32_017_riwayat_versi_tabel_dan_versi_terbaru` | Panel 'Penyerahan Versi 2' dengan label 'Deskripsi perubahan dari kandidat'. Tabel: Versi 2 di atas Versi 1; Versi 2 Menunggu Tinjauan dengan catatan '–'; Versi 1 Perlu Revisi dengan catatan 'Warna label terlalu gelap'; kolom Diserahkan berformat '25 Nov 2026'. Kuota 'Revisi terpakai 1 dari 2'. |
| TC-PBI32-018 | `TC_PBI32_018_Test::test_TC_PBI32_018_script_pada_deskripsi_dan_catatan_tampil_sebagai_teks` | Deskripsi dan catatan tampil sebagai teks mentah di halaman UMKM dan di banner kandidat; tidak ada dialog alert. |
| TC-PBI32-019 | `TC_PBI32_019_Test::test_TC_PBI32_019_tombol_tinjau_hasil_di_detail_pelamar` | Pelamar dengan kesepakatan: tombol 'Tinjau Hasil' tampil dan membuka halaman Tinjau Hasil Pekerjaan; menu 'Kesepakatan & Progres' aktif. Pelamar tanpa kesepakatan: tombol tidak tampil. |
| TC-PBI32-020 | `TC_PBI32_020_Test::test_TC_PBI32_020_umpan_balik_form_keputusan` | Setelah galat: opsi 'Minta revisi' tetap terpilih, catatan tetap terisi, penghitung 2001/2000. Penghitung 30/2000. Setelah diklik tombol berubah 'Menyimpan…' dan nonaktif. |
